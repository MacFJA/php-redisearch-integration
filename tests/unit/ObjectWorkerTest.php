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

namespace MacFJA\RediSearch\Integration\tests\unit;

use MacFJA\RediSearch\IndexBuilder;
use MacFJA\RediSearch\Integration\Event\After;
use MacFJA\RediSearch\Integration\Event\Before;
use MacFJA\RediSearch\Integration\Exception\NoDocumentIdException;
use MacFJA\RediSearch\Integration\Mapping\SuggestionMapping;
use MacFJA\RediSearch\Integration\ObjectWorker;
use MacFJA\RediSearch\Integration\SimpleObjectFactory;
use MacFJA\RediSearch\Integration\SimpleProvider;
use MacFJA\RediSearch\Integration\tests\fixtures\City;
use MacFJA\RediSearch\Integration\tests\fixtures\CityMapping;
use MacFJA\RediSearch\Integration\Worker\RemoveDocument;
use MacFJA\RediSearch\Redis\Client;
use MacFJA\RediSearch\Redis\Command\Aggregate;
use MacFJA\RediSearch\Redis\Command\AggregateCommand\GroupByOption;
use MacFJA\RediSearch\Redis\Command\AggregateCommand\ReduceOption;
use MacFJA\RediSearch\Redis\Command\Create;
use MacFJA\RediSearch\Redis\Command\Search;
use MacFJA\RediSearch\Redis\Command\SugGet;
use MacFJA\RediSearch\Redis\Response\SuggestionResponseItem;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * @covers \MacFJA\RediSearch\Integration\Event\After\AddingDocumentToSearchEvent
 * @covers \MacFJA\RediSearch\Integration\Event\After\AddingSuggestionEvent
 * @covers \MacFJA\RediSearch\Integration\Event\After\CreatingIndexEvent
 * @covers \MacFJA\RediSearch\Integration\Event\After\GettingAggregateEvent
 * @covers \MacFJA\RediSearch\Integration\Event\After\GettingFacetsEvent
 * @covers \MacFJA\RediSearch\Integration\Event\After\GettingSearchEvent
 * @covers \MacFJA\RediSearch\Integration\Event\After\GettingSuggestionsEvent
 * @covers \MacFJA\RediSearch\Integration\Event\After\RemovingDocumentFromSearchEvent
 * @covers \MacFJA\RediSearch\Integration\Event\Before\AddingDocumentToSearchEvent
 * @covers \MacFJA\RediSearch\Integration\Event\Before\AddingSuggestionEvent
 * @covers \MacFJA\RediSearch\Integration\Event\Before\CreatingIndexEvent
 * @covers \MacFJA\RediSearch\Integration\Event\Before\GettingFacetsEvent
 * @covers \MacFJA\RediSearch\Integration\Event\Before\GettingSuggestionsEvent
 * @covers \MacFJA\RediSearch\Integration\Event\Before\RemovingDocumentFromSearchEvent
 * @covers \MacFJA\RediSearch\Integration\Exception\NoDocumentIdException
 * @covers \MacFJA\RediSearch\Integration\Mapping\SuggestionMapping
 * @covers \MacFJA\RediSearch\Integration\ObjectWorker
 * @covers \MacFJA\RediSearch\Integration\SimpleObjectFactory
 * @covers \MacFJA\RediSearch\Integration\SimpleProvider
 * @covers \MacFJA\RediSearch\Integration\Worker\AddDocument
 * @covers \MacFJA\RediSearch\Integration\Worker\RemoveDocument
 *
 * @internal
 */
class ObjectWorkerTest extends TestCase
{
    /** @var Client|MockObject */
    private $client;
    /** @var EventDispatcherInterface|MockObject */
    private $eventDispatcher;
    /** @var ObjectWorker */
    private $objectWorker;

    protected function setUp(): void
    {
        $this->client = $this->createMock(Client::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->eventDispatcher->method('dispatch')->willReturnArgument(0);
        $provider = new SimpleProvider();
        $provider->addMapping(City::class, new CityMapping());
        $this->objectWorker = new ObjectWorker($this->client, $provider, new SimpleObjectFactory(), $this->eventDispatcher);
        parent::setUp();
    }

    public function testCreateIndex(): void
    {
        $expectedIndexBuilder = (new IndexBuilder())
            ->setIndex('cities')
            ->addTextField('name', false, 2.0, null, true, false)
            ->addTextField('country', false, 0.8, null, true, false)
            ->addNumericField('population', true)
            ->addTagField('languages', '|')
            ->addGeoField('coordinate')
            ->addStopWords('city')
            ->addPrefixes('city-')
        ;

        $expectedCreateCommand = (new Create())
            ->setIndex('cities')
            ->addTextField('name', false, 2.0, null, true, false)
            ->addTextField('country', false, 0.8, null, true, false)
            ->addNumericField('population', true)
            ->addTagField('languages', '|')
            ->addGeoField('coordinate')
            ->setPrefixes('city-')
            ->setStopWords('city')
        ;

        $this->eventDispatcher->expects(static::exactly(2))->method('dispatch')->withConsecutive(
            [new Before\CreatingIndexEvent($expectedIndexBuilder, City::class)],
            [new After\CreatingIndexEvent(City::class, true)]
        );

        $this->objectWorker->createIndex(City::class);

        $this->client
            ->expects(static::once())
            ->method('pipeline')
            ->with($expectedCreateCommand)
            ->willReturn(['OK'])
        ;

        $this->objectWorker->flush();
    }

    public function testPersist(): void
    {
        $city = new City();
        $city
            ->setName('Tokyo')
            ->setCountry('Japan')
            ->setPopulation(37339804)
            ->setCoordinate(['lat' => 35.689722, 'lon' => 139.692222])
            ->setLanguages(['Japanese'])
        ;
        $cityData = [
            'name' => 'Tokyo',
            'country' => 'Japan',
            'coordinate' => '35.689722,139.692222',
            'languages' => 'Japanese',
            'population' => 37339804,
        ];
        $nameSuggestion = (new SuggestionMapping())->setDictionary('place')->setData('Tokyo')->setScore(1.5);
        $countrySuggestion = (new SuggestionMapping())->setDictionary('place')->setData('Japan')->setPayload('country');

        $this->eventDispatcher
            ->expects(static::exactly(6))
            ->method('dispatch')
            ->withConsecutive(
                [new Before\AddingDocumentToSearchEvent($city, $cityData, 'city-tokyo-japan')],
                [new Before\AddingSuggestionEvent($nameSuggestion, $city)],
                [new Before\AddingSuggestionEvent($countrySuggestion, $city)],
                [new After\AddingDocumentToSearchEvent($city, $cityData, 'city-tokyo-japan', false)],
                [new After\AddingSuggestionEvent('place', 'Tokyo', 1.5, false, null, $city)],
                [new After\AddingSuggestionEvent('place', 'Japan', null, false, 'country', $city)]
            )
        ;

        $this->objectWorker->persist($city);

        $this->client
            ->expects(static::once())
            ->method('pipeline')
            ->willReturn([
                5,
                1,
                2,
            ])
        ;

        $this->objectWorker->flush();

        $this->eventDispatcher
            ->expects(static::never())
            ->method('dispatch')
        ;
    }

    public function testPreventPersist(): void
    {
        $this->expectException(NoDocumentIdException::class);

        $city = new City();
        $city
            ->setName('Tokyo')
            ->setCountry('Japan')
            ->setPopulation(37339804)
            ->setCoordinate(['lat' => 35.689722, 'lon' => 139.692222])
            ->setLanguages(['Japanese'])
        ;
        $cityData = [
            'name' => 'Tokyo',
            'country' => 'Japan',
            'coordinate' => '35.689722,139.692222',
            'languages' => 'Japanese',
            'population' => 37339804,
        ];

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher
            ->method('dispatch')
            ->with(new Before\AddingDocumentToSearchEvent($city, $cityData, 'city-tokyo-japan'))
            ->willReturn(new Before\AddingDocumentToSearchEvent($city, $cityData, null))
        ;
        $provider = new SimpleProvider();
        $provider->addMapping(City::class, new CityMapping());
        $objectWorker = new ObjectWorker($this->client, $provider, new SimpleObjectFactory(), $eventDispatcher);

        $objectWorker->persistSearch($city);
    }

    public function testDelete(): void
    {
        $city = new City();
        $city
            ->setName('Tokyo')
            ->setCountry('Japan')
            ->setPopulation(37339804)
            ->setCoordinate(['lat' => 35.689722, 'lon' => 139.692222])
            ->setLanguages(['Japanese'])
        ;

        $this->client->expects(static::once())->method('pipeline')->with(
            new RemoveDocument('city-tokyo-japan')
        )->willReturn([1]);
        $this->eventDispatcher->expects(static::exactly(2))->method('dispatch')->withConsecutive(
            [new Before\RemovingDocumentFromSearchEvent($city, 'city-tokyo-japan')],
            [new After\RemovingDocumentFromSearchEvent($city, 'city-tokyo-japan', true)]
        );

        $this->objectWorker->remove($city);
        $this->objectWorker->flush();
    }

    public function testPreventDelete(): void
    {
        $this->expectException(NoDocumentIdException::class);

        $city = new City();
        $city
            ->setName('Tokyo')
            ->setCountry('Japan')
            ->setPopulation(37339804)
            ->setCoordinate(['lat' => 35.689722, 'lon' => 139.692222])
            ->setLanguages(['Japanese'])
        ;
        $cityData = [
            'name' => 'Tokyo',
            'country' => 'Japan',
            'coordinate' => '35.689722,139.692222',
            'languages' => 'Japanese',
            'population' => 37339804,
        ];

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher
            ->method('dispatch')
            ->with(new Before\RemovingDocumentFromSearchEvent($city, 'city-tokyo-japan'))
            ->willReturn(new Before\RemovingDocumentFromSearchEvent($city, null))
        ;
        $provider = new SimpleProvider();
        $provider->addMapping(City::class, new CityMapping());
        $objectWorker = new ObjectWorker($this->client, $provider, new SimpleObjectFactory(), $eventDispatcher);

        $objectWorker->remove($city);
    }

    public function testGetSuggestions(): void
    {
        $expectedSugGet = (new SugGet())
            ->setDictionary('place')
            ->setPrefix('to')
            ->setFuzzy(false)
            ->setWithPayloads(true)
            ->setWithScores(true)
        ;
        $expectedResult = [new SuggestionResponseItem('tokyo', 0.9, null)];
        $this->client->expects(static::once())->method('pipeline')->with($expectedSugGet)->willReturn(
            [
                $expectedResult,
            ]
        );

        $response = $this->objectWorker->getSuggestions(City::class, 'to');

        static::assertEqualsCanonicalizing(['place' => $expectedResult], $response);
    }

    public function testGetSearchCommand(): void
    {
        $expected = (new Search())->setIndex('cities');
        $first = $this->objectWorker->getSearchCommand(City::class);
        $second = $this->objectWorker->getSearchCommand(City::class);

        static::assertEquals($expected, $first);
        static::assertEquals($expected, $second);
        static::assertNotSame($first, $second);
    }

    public function testGetAggregateCommand(): void
    {
        $expected = (new Aggregate())->setIndex('cities');
        $first = $this->objectWorker->getAggregateCommand(City::class);
        $second = $this->objectWorker->getAggregateCommand(City::class);

        static::assertEquals($expected, $first);
        static::assertEquals($expected, $second);
        static::assertNotSame($first, $second);
    }

    public function testGetFacets(): void
    {
        $expected = ['country' => ['Japan' => 2, 'USA' => 23], 'city' => ['Tokyo' => 2, 'New York City' => 9, 'San Fransisco' => 4, 'Los Angeles' => 5, 'Chicago' => 5]];
        $this->client->expects(static::once())->method('pipeline')->with(
            (new Aggregate())->setIndex('cities')->setQuery('*')->addGroupBy((new GroupByOption(['country'], [ReduceOption::count('count')])))->addSortBy('count', 'DESC')->setLimit(0, 30),
            (new Aggregate())->setIndex('cities')->setQuery('*')->addGroupBy((new GroupByOption(['city'], [ReduceOption::count('count')])))->addSortBy('count', 'DESC')->setLimit(0, 30)
        )->willReturn([
            (new Aggregate())->parseResponse([2, ['country', 'Japan', 'count', 2], ['country', 'USA', 'count', 23]]),
            (new Aggregate())->parseResponse([5, ['city', 'Tokyo', 'count', 2], ['city', 'New York City', 'count', 9], ['city', 'San Fransisco', 'count', 4], ['city', 'Los Angeles', 'count', 5], ['city', 'Chicago', 'count', 5]]),
        ]);

        $actual = $this->objectWorker->getFacets(City::class, '*', ['country', 'city']);

        static::assertSame($expected, $actual);
    }
}
