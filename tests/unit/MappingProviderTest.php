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

use MacFJA\RediSearch\Integration\Annotation\AnnotationProvider;
use MacFJA\RediSearch\Integration\CompositeProvider;
use MacFJA\RediSearch\Integration\Json\JsonProvider;
use MacFJA\RediSearch\Integration\Mapping;
use MacFJA\RediSearch\Integration\Mapping\SuggestionMapping;
use MacFJA\RediSearch\Integration\SimpleProvider;
use MacFJA\RediSearch\Integration\tests\fixtures\Book;
use MacFJA\RediSearch\Integration\tests\fixtures\Car;
use MacFJA\RediSearch\Integration\tests\fixtures\City;
use MacFJA\RediSearch\Integration\tests\fixtures\CityMapping;
use MacFJA\RediSearch\Integration\Xml\XmlProvider;
use MacFJA\RediSearch\Redis\Command\CreateCommand\GeoFieldOption;
use MacFJA\RediSearch\Redis\Command\CreateCommand\NumericFieldOption;
use MacFJA\RediSearch\Redis\Command\CreateCommand\TagFieldOption;
use MacFJA\RediSearch\Redis\Command\CreateCommand\TextFieldOption;

/**
 * @covers \MacFJA\RediSearch\Integration\Annotation\AnnotationMapping
 * @covers \MacFJA\RediSearch\Integration\Annotation\AnnotationProvider
 * @covers \MacFJA\RediSearch\Integration\AnnotationAttribute\AbstractMapping
 * @covers \MacFJA\RediSearch\Integration\AnnotationAttribute\Index
 * @covers \MacFJA\RediSearch\Integration\AnnotationAttribute\NameNormalizer
 * @covers \MacFJA\RediSearch\Integration\AnnotationAttribute\NumericField
 * @covers \MacFJA\RediSearch\Integration\AnnotationAttribute\ReflectionFieldValue
 * @covers \MacFJA\RediSearch\Integration\AnnotationAttribute\Suggestion
 * @covers \MacFJA\RediSearch\Integration\AnnotationAttribute\TagField
 * @covers \MacFJA\RediSearch\Integration\AnnotationAttribute\TextField
 * @covers \MacFJA\RediSearch\Integration\CompositeProvider
 * @covers \MacFJA\RediSearch\Integration\Json\JsonMapping
 * @covers \MacFJA\RediSearch\Integration\Json\JsonProvider
 * @covers \MacFJA\RediSearch\Integration\Mapping\SuggestionMapping
 * @covers \MacFJA\RediSearch\Integration\SimpleProvider
 * @covers \MacFJA\RediSearch\Integration\Xml\XmlMapping
 * @covers \MacFJA\RediSearch\Integration\Xml\XmlProvider
 *
 * @internal
 */
class MappingProviderTest extends \PHPUnit\Framework\TestCase
{
    public function testJsonProvider(): void
    {
        $provider = new JsonProvider();

        static::assertFalse($provider->hasMappingFor(Book::class));
        static::assertFalse($provider->hasMappingFor(Car::class));

        $provider->addJson(__DIR__.'/../fixtures/Book.json');

        static::assertTrue($provider->hasMappingFor(Book::class));
        static::assertFalse($provider->hasMappingFor(Car::class));

        $book = new Book();
        $book->title = 'The Hobbit or There And Back Again';
        $book->author = 'J. R. R. Tolkien';
        $book->isbn = '978-0-261-10221-7';
        $book->page = 389;

        $mapping = $provider->getMapping($book);
        $mappingClass = $provider->getMapping(Book::class);

        static::assertSame($mapping, $mappingClass);

        static::assertNotNull($mapping);

        static::assertSame('books', $mapping->getIndexName());
        static::assertSame('book-', $mapping->getDocumentPrefix());
        static::assertEqualsCanonicalizing(['chapter', 'volume'], $mapping->getStopWords());
        static::assertSame('book-9780261102217', $mapping->getDocumentId($book));
        static::assertEquals([
            'page' => 389,
            'title' => 'The Hobbit or There And Back Again',
            'isbn' => 9780261102217,
            'rawIsbn' => '978-0-261-10221-7',
            'author' => 'J. R. R. Tolkien',
        ], $mapping->getData($book));
        static::assertEqualsCanonicalizing(['suggestion', 'person'], $mapping->getSuggestionGroups());
        static::assertEqualsCanonicalizing([
            (new SuggestionMapping())->setData('The Hobbit or There And Back Again')->setDictionary('suggestion'),
            (new SuggestionMapping())->setData('J. R. R. Tolkien')->setDictionary('person'),
        ], $mapping->getSuggestion($book));
    }

    public function testXmlProvider(): void
    {
        $provider = new XmlProvider();

        static::assertFalse($provider->hasMappingFor(City::class));
        static::assertFalse($provider->hasMappingFor(Car::class));

        $provider->addXml(__DIR__.'/../fixtures/City.xml');

        static::assertTrue($provider->hasMappingFor(City::class));
        static::assertFalse($provider->hasMappingFor(Car::class));

        $city = new City();
        $city
            ->setName('Tokyo')
            ->setCountry('Japan')
            ->setPopulation(37339804)
            ->setCoordinate(['lat' => 35.689722, 'lon' => 139.692222])
            ->setLanguages(['Japanese'])
        ;

        $mapping = $provider->getMapping($city);
        $mappingClass = $provider->getMapping(City::class);

        static::assertSame($mapping, $mappingClass);

        static::assertNotNull($mapping);

        static::assertSame('cities', $mapping->getIndexName());
        static::assertSame('city-', $mapping->getDocumentPrefix());
        static::assertNull($mapping->getDocumentId($city));
        static::assertEquals([
            'name' => 'Tokyo',
            'country' => 'Japan',
            'population' => 37339804,
            'languages' => 'Japanese',
            'coordinate' => '35.689722,139.692222',
        ], $mapping->getData($city));
        static::assertEqualsCanonicalizing(['place'], $mapping->getSuggestionGroups());
        static::assertEqualsCanonicalizing([
            (new SuggestionMapping())->setData('Tokyo')->setDictionary('place')->setScore(1.5),
            (new SuggestionMapping())->setData('Japan')->setDictionary('place')->setPayload('country'),
        ], $mapping->getSuggestion($city));
        static::assertNull($mapping->getStopWords());

        static::assertEquals([
            'name' => (new TextFieldOption())->setField('name')->setNoStem(false)->setWeight(2.0)->setNoIndex(false)->setSortable(true),
            'country' => (new TextFieldOption())->setField('country')->setNoStem(false)->setWeight(0.8)->setNoIndex(false)->setSortable(true),
            'population' => (new NumericFieldOption())->setField('population')->setNoIndex(false)->setSortable(true),
            'languages' => (new TagFieldOption())->setField('languages')->setNoIndex(false)->setSortable(false)->setSeparator('|'),
            'coordinate' => (new GeoFieldOption())->setField('coordinate')->setNoIndex(false),
        ], $mapping->getFields());
    }

    public function testAnnotationProvider(): void
    {
        $provider = new AnnotationProvider();

        static::assertFalse($provider->hasMappingFor(City::class));
        static::assertTrue($provider->hasMappingFor(Car::class));

        $car = new Car();
        $car
            ->setModel('Corolla')
            ->setMaker('Toyota')
            ->setYear(2019)
            ->setType('Compact')
        ;

        $mapping = $provider->getMapping($car);
        $mappingClass = $provider->getMapping(Car::class);

        static::assertSame($mapping, $mappingClass);

        static::assertNotNull($mapping);

        static::assertSame('car', $mapping->getIndexName());
        static::assertSame('car-', $mapping->getDocumentPrefix());
        static::assertSame('car-toyota-corolla-2019', $mapping->getDocumentId($car));
        static::assertEquals([
            'model' => 'Corolla',
            'maker' => 'Toyota',
            'year' => 2019,
            'type' => 'Compact',
        ], $mapping->getData($car));
        static::assertEqualsCanonicalizing(['brand', 'suggestion'], $mapping->getSuggestionGroups());
        static::assertEquals([
            (new SuggestionMapping())->setData('Corolla')->setDictionary('brand'),
            (new SuggestionMapping())->setData('Toyota')->setDictionary('brand')->setScore(0.7),
            (new SuggestionMapping())->setData('Compact')->setDictionary('suggestion')->setScore(0.3)->setIncrement(true),
        ], $mapping->getSuggestion($car));
    }

    public function testSimpleProvider(): void
    {
        $provider = new SimpleProvider();

        static::assertFalse($provider->hasMappingFor(City::class));
        static::assertFalse($provider->hasMappingFor(Car::class));

        $sourceMapping = new CityMapping();

        $provider->addMapping(City::class, $sourceMapping);

        static::assertTrue($provider->hasMappingFor(City::class));
        static::assertFalse($provider->hasMappingFor(Car::class));

        $city = new City();
        $city
            ->setName('Delhi')
            ->setCountry('India')
            ->setPopulation(28514000)
            ->setCoordinate(['lat' => 28.61, 'lon' => 77.23])
            ->setLanguages(['Hindi', 'English'])
        ;

        $mapping = $provider->getMapping($city);
        $mappingClass = $provider->getMapping(City::class);

        static::assertSame($mapping, $mappingClass);
        static::assertSame($mapping, $sourceMapping);

        static::assertNotNull($mapping);

        static::assertSame('cities', $mapping->getIndexName());
        static::assertSame('city-', $mapping->getDocumentPrefix());
        static::assertSame('city-delhi-india', $mapping->getDocumentId($city));
        static::assertEquals([
            'name' => 'Delhi',
            'country' => 'India',
            'population' => 28514000,
            'languages' => 'Hindi|English',
            'coordinate' => '28.61,77.23',
        ], $mapping->getData($city));
        static::assertEqualsCanonicalizing(['place'], $mapping->getSuggestionGroups());
        static::assertEqualsCanonicalizing([
            (new SuggestionMapping())->setData('Delhi')->setDictionary('place')->setScore(1.5),
            (new SuggestionMapping())->setData('India')->setDictionary('place')->setPayload('country'),
        ], $mapping->getSuggestion($city));
    }

    public function testCompositeProvider(): void
    {
        $json = new JsonProvider();
        $json->addJson(__DIR__.'/../fixtures/Book.json');
        $annotation = new AnnotationProvider();
        $xml = new XmlProvider();

        $provider = new CompositeProvider();
        $provider->addProvider($json);
        $provider->addProvider($annotation, true);
        $provider->addProvider($xml);

        static::assertFalse($provider->hasMappingFor(City::class));
        // @phpstan-ignore-next-line
        $provider->addXml(__DIR__.'/../fixtures/City.xml');
        static::assertTrue($provider->hasMappingFor(City::class));
        static::assertTrue($provider->hasMappingFor(Car::class));
        static::assertTrue($provider->hasMappingFor(Book::class));
        static::assertFalse($provider->hasMappingFor(self::class));

        $mapping = $provider->getMapping(City::class);
        static::assertInstanceOf(Mapping::class, $mapping);
        static::assertEquals('cities', $mapping->getIndexName());
    }
}
