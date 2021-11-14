<?php

declare(strict_types=1);

/*
 * Copyright MacFJA
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated
 * documentation files (the "Software"), to deal in the Software without restriction, including without limitation the
 * rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to
 * permit persons to whom the Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all copies or substantial portions of the
 * Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE
 * WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
 * COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR
 * OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

namespace MacFJA\RediSearch\Integration;

use function array_key_exists;
use function assert;
use function count;
use function get_class;
use function in_array;
use function is_array;
use function is_callable;
use function is_float;
use function is_int;
use function is_string;
use MacFJA\RediSearch\Integration\Event\After;
use MacFJA\RediSearch\Integration\Event\Before;
use MacFJA\RediSearch\Integration\Event\Event;
use MacFJA\RediSearch\Integration\Exception\MappingNotFoundException;
use MacFJA\RediSearch\Integration\Exception\NoDocumentIdException;
use MacFJA\RediSearch\Integration\Iterator\ResponseItemIterator;
use MacFJA\RediSearch\Integration\Mapping\SuggestionMapping;
use MacFJA\RediSearch\Integration\Worker\AddDocument;
use MacFJA\RediSearch\Integration\Worker\RemoveDocument;
use MacFJA\RediSearch\Redis\Client;
use MacFJA\RediSearch\Redis\Command;
use MacFJA\RediSearch\Redis\Command\Aggregate;
use MacFJA\RediSearch\Redis\Command\CreateCommand\CreateCommandFieldOption;
use MacFJA\RediSearch\Redis\Command\Search;
use MacFJA\RediSearch\Redis\Response;
use MacFJA\RediSearch\Redis\Response\PaginatedResponse;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ObjectWorker implements ObjectManager, ObjectRepository
{
    /** @var array<array{"command":Command,"event":callable|null}> */
    private $commandPipeline = [];
    /** @var Client */
    private $client;
    /** @var MappingProvider */
    private $mappingProvider;
    /** @var ObjectFactory */
    private $objectFactory;
    /** @var null|EventDispatcherInterface */
    private $eventDispatcher;

    public function __construct(Client $client, MappingProvider $mappingProvider, ?ObjectFactory $objectFactory = null, ?EventDispatcherInterface $eventDispatcher = null)
    {
        $this->client = $client;
        $this->mappingProvider = $mappingProvider;
        $this->objectFactory = $objectFactory ?? new SimpleObjectFactory();
        $this->eventDispatcher = $eventDispatcher;
    }

    public function createIndex($forObject): void
    {
        $mapping = $this->getMapping($forObject);

        $classname = is_string($forObject) ? $forObject : get_class($forObject);

        $indexBuilder = $this->objectFactory->getIndexBuilder();
        $indexBuilder
            ->setIndex($mapping->getIndexName())
        ;
        if ($mapping->getDocumentPrefix()) {
            $indexBuilder->setPrefixes([$mapping->getDocumentPrefix()]);
        }
        $stopWords = $mapping->getStopWords();
        if (is_array($stopWords)) {
            $indexBuilder->setStopWords($stopWords);
        }
        foreach ($mapping->getFields() as $field) {
            $indexBuilder->addField($field);
        }

        $event = new Before\CreatingIndexEvent($indexBuilder, $classname);
        $responseEvent = $this->sendEvent($event);

        $this->addToPipeline(
            $responseEvent->getBuilder()->getCommand(),
            static function ($response) use ($classname) {
                return new After\CreatingIndexEvent($classname, 'OK' === (string) $response);
            }
        );
    }

    public function persist(object $object): void
    {
        $this->persistSearch($object);
        $this->persistSuggestions($object);
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedLocalVariable) -- Bug on PHPMD side (https://github.com/phpmd/phpmd/issues/601)
     */
    public function persistSearch(object $object): void
    {
        $mapping = $this->getMapping($object);

        $event = new Before\AddingDocumentToSearchEvent(
            $object,
            $mapping->getData($object),
            $mapping->getDocumentId($object) ??
                uniqid($mapping->getDocumentPrefix() ?? 'gen_', true)
        );
        $eventResponse = $this->sendEvent($event);

        $documentId = $eventResponse->getDocumentId();
        if (null === $documentId) {
            throw new NoDocumentIdException();
        }

        $this->addToPipeline(
            new AddDocument($documentId, $eventResponse->getData()),
            static function ($response) use ($documentId, $eventResponse, $object) {
                return new After\AddingDocumentToSearchEvent(
                    $object,
                    $eventResponse->getData(),
                    $documentId,
                    (int) $response < count($eventResponse->getData())
                );
            }
        );
    }

    public function persistSuggestions(object $object): void
    {
        $mapping = $this->getMapping($object);

        /** @var SuggestionMapping $suggestion */
        foreach ($mapping->getSuggestion($object) as $suggestion) {
            $event = new Before\AddingSuggestionEvent(
                $suggestion,
                $object
            );
            $eventResponse = $this->sendEvent($event);
            $command = $this->objectFactory->createSugAddCommand()
                ->setDictionary($eventResponse->getSuggestionMapping()->getDictionary() ?? 'suggestion')
                ->setSuggestion($eventResponse->getSuggestionMapping()->getData())
                ->setIncrement($eventResponse->getSuggestionMapping()->isIncrement())
            ;
            $payload = $eventResponse->getSuggestionMapping()->getPayload();
            if (is_string($payload)) {
                $command->setPayload($payload);
            }
            $score = $eventResponse->getSuggestionMapping()->getScore();
            if (is_float($score)) {
                $command->setScore($score);
            }
            $this->addToPipeline(
                $command,
                static function () use ($object, $eventResponse) {
                    return new After\AddingSuggestionEvent(
                        $eventResponse->getSuggestionMapping()->getDictionary() ?? 'suggestion',
                        $eventResponse->getSuggestionMapping()->getData(),
                        $eventResponse->getSuggestionMapping()->getScore(),
                        $eventResponse->getSuggestionMapping()->isIncrement(),
                        $eventResponse->getSuggestionMapping()->getPayload(),
                        $object
                    );
                }
            );
        }
    }

    public function remove(object $object): void
    {
        $mapping = $this->getMapping($object);

        $event = new Before\RemovingDocumentFromSearchEvent($object, $mapping->getDocumentId($object));
        $response = $this->sendEvent($event);

        $documentId = $response->getDocumentId();

        if (null === $documentId) {
            throw new NoDocumentIdException();
        }

        $this->addToPipeline(
            new RemoveDocument($documentId),
            static function ($response) use ($documentId, $object) {
                return new After\RemovingDocumentFromSearchEvent(
                    $object,
                    $documentId,
                    1 === (int) $response
                );
            }
        );
    }

    public function flush(): void
    {
        $responses = $this->client->pipeline(...array_column($this->commandPipeline, 'command'));
        $sourceCommands = $this->commandPipeline;
        $this->commandPipeline = [];
        foreach ($sourceCommands as $index => $sourceCommand) {
            $after = $sourceCommand['event'];
            if (is_callable($after)) {
                $event = $after($responses[$index], $sourceCommand['command']);
                assert($event instanceof Event);
                $this->sendEvent($event);
            }
        }
    }

    public function isIndexInSync($object): bool
    {
        $mapping = $this->getMapping($object);

        $list = $this->client->execute($this->objectFactory->getIndexListCommand());
        if (!in_array($mapping->getIndexName(), $list, true)) {
            return false;
        }
        /** @var Response\InfoResponse $info */
        $info = $this->client->execute($this->objectFactory->createIndexInfoCommand()->setIndex($mapping->getIndexName()));
        $currentPrefixes = $info->getIndexDefinition('prefixes');
        $documentPrefix = $mapping->getDocumentPrefix();
        if (count($currentPrefixes) > 0 && !(reset($currentPrefixes) === $documentPrefix)) {
            return false;
        }

        if (is_string($documentPrefix) && 0 === count($currentPrefixes)) {
            return false;
        }

        $fields = $mapping->getFields();
        /** @var CreateCommandFieldOption $option */
        foreach ($info->getFieldsAsOption() as $option) {
            if (!array_key_exists($option->getFieldName(), $fields)) {
                return false;
            }
            if (!($option->getOptionData() === $fields[$option->getFieldName()]->getOptionData())) {
                return false;
            }
        }

        return true;
    }

    public function getSuggestions(string $classname, string $prefix, ?string $inGroup = null): array
    {
        $mapping = $this->getMapping($classname);

        $event = new Before\GettingSuggestionsEvent(
            $classname,
            $prefix,
            false,
            true,
            true,
            null,
            $inGroup
        );
        $responseEvent = $this->sendEvent($event);

        $baseCommand = $this->objectFactory->createSugGetCommand()
            ->setPrefix($responseEvent->getPrefix())
            ->setWithPayloads($responseEvent->isWithPayloads())
            ->setWithScores($responseEvent->isWithScores())
            ->setFuzzy($responseEvent->isFuzzy())
        ;
        $max = $responseEvent->getMax();
        if (is_int($max)) {
            $baseCommand->setMax($max);
        }
        $inGroup = $responseEvent->getInGroup();

        if (is_string($inGroup)) {
            $groups = [$inGroup];
        } else {
            $groups = $mapping->getSuggestionGroups();
        }

        $commands = array_map(static function (string $dictionary) use ($baseCommand) {
            $cloned = clone $baseCommand;
            $cloned->setDictionary($dictionary);

            return $cloned;
        }, $groups);
        $pipeline = $this->client->pipeline(...$commands);

        $suggestions = array_column(array_map(static function (array $pipelineResult, string $group) {
            return [$group, $pipelineResult];
        }, $pipeline, $groups), 1, 0);

        $event = new After\GettingSuggestionsEvent(
            $responseEvent->getPrefix(),
            $responseEvent->isFuzzy(),
            $responseEvent->isWithScores(),
            $responseEvent->isWithPayloads(),
            $responseEvent->getMax(),
            $classname,
            $inGroup,
            $suggestions
        );
        $responseEvent = $this->sendEvent($event);

        return $responseEvent->getSuggestions();
    }

    public function getSearchCommand(string $classname): Search
    {
        $mapping = $this->getMapping($classname);

        $search = $this->objectFactory->createSearchCommand()->setIndex($mapping->getIndexName());

        $event = new After\GettingSearchEvent($search, $classname);
        $response = $this->sendEvent($event);

        return $response->getSearchBuilder();
    }

    public function getAggregateCommand(string $classname): Aggregate
    {
        $mapping = $this->getMapping($classname);

        $aggregate = $this->objectFactory->createAggregateCommand()->setIndex($mapping->getIndexName());
        $event = new After\GettingAggregateEvent($aggregate, $classname);
        $response = $this->sendEvent($event);

        return $response->getAggregate();
    }

    public function getFacets(string $classname, string $query, array $fields): array
    {
        $event = new Before\GettingFacetsEvent($classname, $query, $fields);
        $response = $this->sendEvent($event);

        $baseAggregate = $this->getAggregateCommand($classname)
            ->setQuery($response->getQuery())
            ->setLimit(0, 30)
            ->addSortBy('count', 'DESC')
        ;

        $commands = array_map(static function (string $field) use ($baseAggregate) {
            $fieldAggregate = clone $baseAggregate;
            $fieldAggregate->addGroupBy(new Command\AggregateCommand\GroupByOption(
                [$field],
                [Command\AggregateCommand\ReduceOption::count('count')]
            ));

            return $fieldAggregate;
        }, $response->getFields());

        $facets = $this->client->pipeline(...$commands);

        $facets = array_reduce($facets, static function ($carry, PaginatedResponse $response) {
            $result = $response->current();
            foreach ($result as $item) {
                assert($item instanceof Response\AggregateResponseItem);
                $fields = array_keys($item->getFields());
                $fields = array_filter($fields, static function (string $field): bool { return !('count' === $field); });
                $field = reset($fields);
                if (!is_string($field)) {
                    continue;
                }
                $carry[$field][$item->getValue($field)] = $item->getValue('count');
            }

            return $carry;
        }, []);

        $event = new After\GettingFacetsEvent($classname, $response->getQuery(), $response->getFields(), $facets);
        $response = $this->sendEvent($event);

        return $response->getFacets();
    }

    public function getAllPaginatedResponseArray(Command\PaginatedCommand $paginatedCommand): array
    {
        // Preflight
        $preflight = clone $paginatedCommand;
        $preflight->setLimit(0, 0);
        /** @var PaginatedResponse $preflightResponse */
        $preflightResponse = $this->client->execute($preflight);

        // Full request
        $full = clone $paginatedCommand;
        $full->setLimit(0, $preflightResponse->getTotalCount());
        /** @var PaginatedResponse $fullResponse */
        $fullResponse = $this->client->execute($full);

        return $fullResponse->current();
    }

    public static function getAllPaginatedResponseIterator(Command\PaginatedCommand $paginatedCommand, Client $client): ResponseItemIterator
    {
        return new ResponseItemIterator($client->execute($paginatedCommand), $client);
    }

    /**
     * @param object|string $classname
     */
    private function getMapping($classname): Mapping
    {
        $mapping = $this->mappingProvider->getMapping($classname);
        if (!$mapping instanceof Mapping) {
            throw new MappingNotFoundException(is_string($classname) ? $classname : get_class($classname));
        }

        return $mapping;
    }

    private function addToPipeline(Command $command, ?callable $afterEventGenerator = null): void
    {
        $this->commandPipeline[] = ['command' => $command, 'event' => $afterEventGenerator];
    }

    /**
     * @phpstan-template T of Event
     * @phpstan-param T $event
     * @phpstan-return T
     */
    private function sendEvent(Event $event): Event
    {
        if ($this->eventDispatcher instanceof EventDispatcherInterface) {
            return $this->eventDispatcher->dispatch($event);
        }

        return $event;
    }
}
