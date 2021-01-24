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

use function array_filter;
use function count;
use function get_class;
use function in_array;
use function is_string;
use MacFJA\RediSearch\Helper\PaginatedResult;
use MacFJA\RediSearch\Index\Builder;
use MacFJA\RediSearch\Integration\Event\After;
use MacFJA\RediSearch\Integration\Event\Before;
use MacFJA\RediSearch\Integration\Exception\NotMappedException;
use MacFJA\RediSearch\Integration\Exception\UnidentifiableDocumentException;
use MacFJA\RediSearch\Search;
use MacFJA\RediSearch\Suggestion\Result;
use SGH\Comparable\SetFunctions;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ObjectManager
{
    /** @var Builder */
    private $builder;

    /** @var IndexObjectFactory */
    private $objectFactory;

    /** @var MappedClassProvider */
    private $provider;

    /** @var \Psr\EventDispatcher\EventDispatcherInterface */
    private $eventDispatcher;

    public function __construct(IndexObjectFactory $objectFactory, MappedClassProvider $provider)
    {
        $this->builder = $objectFactory->getIndexBuilder();
        $this->objectFactory = $objectFactory;
        $this->provider = $provider;
        $this->eventDispatcher = $objectFactory->getEventDispatcher();
    }

    public function createIndex(string $className): bool
    {
        $mapped = $this->provider->getStaticMappedClass($className);
        if (null === $mapped) {
            throw new NotMappedException($className);
        }

        $indexName = $mapped::getRSIndexName();
        $fields = $mapped::getRSFieldsDefinition();

        $this->builder->reset();
        $this->builder->withName($indexName);
        foreach ($fields as $field) {
            $this->builder->addField($field);
        }

        /** @var Before\CreatingIndexEvent $event */
        $event = $this->eventDispatcher->dispatch(new Before\CreatingIndexEvent($this->builder, $className));

        $succeed = $event->getBuilder()->execute();

        $this->eventDispatcher->dispatch(new After\CreatingIndexEvent($className, $succeed));

        return $succeed;
    }

    /**
     * @param object $instance
     *
     * @throws NotMappedException
     */
    public function addObject($instance): string
    {
        $this->addObjectInSuggestion($instance);

        return $this->addObjectInSearch($instance);
    }

    /**
     * @param object $instance
     *
     * @throws NotMappedException
     */
    public function addObjectInSearch($instance): string
    {
        $mapped = $this->provider->getMappedClass($instance);

        if (null === $mapped) {
            throw new NotMappedException(get_class($instance));
        }

        $data = array_filter($mapped->getRSDataArray($instance));
        $indexName = $mapped::getRSIndexName();

        $index = $this->objectFactory->getIndex($indexName);
        /** @var Before\AddingDocumentToSearchEvent $event */
        $event = $this->eventDispatcher->dispatch(new Before\AddingDocumentToSearchEvent($instance, $data, $mapped->getRSDocumentId($instance)));

        $documentId = $index->addDocumentFromArray($event->getData(), $event->getDocumentId());

        $this->eventDispatcher->dispatch(new After\AddingDocumentToSearchEvent($instance, $event->getData(), $documentId));

        return $documentId;
    }

    /**
     * @param object $instance
     *
     * @throws NotMappedException
     */
    public function addObjectInSuggestion($instance): void
    {
        $mapped = $this->provider->getMappedClass($instance);

        if (null === $mapped) {
            throw new NotMappedException(get_class($instance));
        }

        $suggestionsSources = $mapped->getRSSuggestions($instance);
        $suggestions = [];

        foreach ($suggestionsSources as $group => $groupOfSuggestion) {
            foreach ($groupOfSuggestion as $suggestion) {
                /** @var Before\AddingSuggestionEvent $event */
                $event = $this->eventDispatcher->dispatch(new Before\AddingSuggestionEvent(
                    $group,
                    $suggestion['value'],
                    $suggestion['score'],
                    $suggestion['increment'],
                    $suggestion['payload'],
                    $instance
                ));
                $suggestions[$event->getGroup()][] = [
                    'value' => $event->getSuggestion(),
                    'score' => $event->getScore(),
                    'increment' => $event->isIncrement(),
                    'payload' => $event->getPayload(),
                ];
            }
        }

        foreach ($suggestions as $group => $groupOfSuggestion) {
            $suggestionsObject = $this->objectFactory->getSuggestion((string) $group);
            foreach ($groupOfSuggestion as $suggestion) {
                $suggestionsObject->add($suggestion['value'], $suggestion['score'], $suggestion['increment'], $suggestion['payload']);
                $this->eventDispatcher->dispatch(new After\AddingSuggestionEvent(
                    (string) $group,
                    $suggestion['value'], $suggestion['score'], $suggestion['increment'], $suggestion['payload'],
                    $instance
                ));
            }
        }
    }

    /**
     * @param object $instance
     */
    public function removeObjectFromSearch($instance, ?string $hash = null): bool
    {
        $mapped = $this->provider->getMappedClass($instance);

        if (null === $mapped) {
            throw new NotMappedException(get_class($instance));
        }

        /** @var Before\RemovingDocumentFromSearchEvent $event */
        $event = $this->eventDispatcher->dispatch(new Before\RemovingDocumentFromSearchEvent($instance, $hash ?? $mapped->getRSDocumentId($instance)));

        if (!is_string($event->getDocumentId())) {
            throw new UnidentifiableDocumentException();
        }

        $indexName = $mapped::getRSIndexName();

        $index = $this->objectFactory->getIndex($indexName);

        $succeed = $index->deleteDocument($event->getDocumentId());

        $this->eventDispatcher->dispatch(new After\RemovingDocumentFromSearchEvent($index, $event->getDocumentId(), $succeed));

        return $succeed;
    }

    /**
     * @param string|class-string $classname
     *
     * @return array<string,array<Result>>
     */
    public function getSuggestions(string $classname, string $prefix, ?string $inGroup = null): array
    {
        $mapped = $this->provider->getStaticMappedClass($classname);

        if (null === $mapped) {
            throw new NotMappedException($classname);
        }

        /** @var Before\GettingSuggestionsEvent $event */
        $event = $this->eventDispatcher->dispatch(new Before\GettingSuggestionsEvent(
            $classname,
            $prefix,
            true,
            true,
            true,
            null,
            $inGroup
        ));

        $inGroup = $event->getInGroup();
        $groups = $mapped::getRSSuggestionGroups();
        if (is_string($inGroup) && !in_array($inGroup, $groups, true)) {
            return [];
        }
        if (is_string($inGroup)) {
            $groups = [$inGroup];
        }

        $suggestions = [];
        foreach ($groups as $group) {
            $group = (string) $group;
            $suggestionsObject = $this->objectFactory->getSuggestion($group);
            $results = $suggestionsObject->get(
                $event->getPrefix(),
                $event->isFuzzy(),
                $event->isWithScores(),
                $event->isWithPayloads(),
                $event->getMax()
            );
            if (count($results) > 0) {
                $suggestions[$group] = $results;
            }
        }

        /** @var After\GettingSuggestionsEvent $afterEvent */
        $afterEvent = $this->eventDispatcher->dispatch(new After\GettingSuggestionsEvent(
            $event->getPrefix(),
            $event->isFuzzy(),
            $event->isWithScores(),
            $event->isWithPayloads(),
            $event->getMax(),
            $classname,
            $event->getInGroup(),
            $suggestions
        ));

        return $afterEvent->getSuggestions();
    }

    public function getSearchBuilder(string $classname): Search
    {
        $mapped = $this->provider->getStaticMappedClass($classname);

        if (null === $mapped) {
            throw new NotMappedException($classname);
        }

        $indexName = $mapped::getRSIndexName();

        $search = $this->objectFactory->getSearch();
        $search->reset();
        /** @var After\GettingSearchBuilderEvent $event */
        $event = $this->eventDispatcher->dispatch(new After\GettingSearchBuilderEvent(
            $search->withIndex($indexName),
            $classname
        ));

        return $event->getSearchBuilder();
    }

    public function isIndexDefinitionSync(string $classname): bool
    {
        $mapped = $this->provider->getStaticMappedClass($classname);

        if (null === $mapped) {
            throw new NotMappedException($classname);
        }

        $indexName = $mapped::getRSIndexName();
        $fields = $mapped::getRSFieldsDefinition();

        $index = $this->objectFactory->getIndex($indexName);
        $stats = $index->getStats();
        $indexFields = $stats->getFieldsAsObject();

        return count(SetFunctions::diff($fields, $indexFields)) > 0;
    }

    /**
     * @return PaginatedResult<Search\Result>
     */
    public static function getAllResults(Search $search): PaginatedResult
    {
        $preflight = clone $search;
        $preflightResult = $preflight
            ->withResultOffset(0)
            ->withResultLimit(0)
            ->execute();

        return $search->withResultLimit($preflightResult->getTotalCount())->execute();
    }
}
