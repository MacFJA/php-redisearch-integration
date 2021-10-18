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

use function get_class;
use function serialize;
use Exception;
use MacFJA\RediSearch\Index\Builder;
use MacFJA\RediSearch\Integration\Json\JsonProvider;
use MacFJA\RediSearch\Integration\MappedClass;
use MacFJA\RediSearch\Integration\tests\fixtures\json\Person;
use PHPUnit\Framework\TestCase;

/**
 * @covers \MacFJA\RediSearch\Integration\Json\JsonProvider
 * @covers \MacFJA\RediSearch\Integration\Json\TemplateJsonMapper
 *
 * @uses \MacFJA\RediSearch\Integration\Helper\MapperHelper
 */
class JsonMapperTest extends TestCase
{
    public function testNominalCase()
    {
        $provider = new JsonProvider();
        $provider->addJson(__DIR__.'/../fixtures/json/example.json');
        $className = $provider->getStaticMappedClass(Person::class);
        $className2 = $provider->getStaticMappedClass(Person::class);

        self::assertNotNull($className);
        self::assertNotNull($className2);
        self::assertSame($className, $className2);
        self::assertTrue($provider->hasMappingFor(Person::class));
        self::assertFalse($provider->hasMappingFor('A'));
        self::assertFalse($provider->hasMappingFor(self::class));

        $instance = new Person();
        $mapped = $provider->getMappedClass($instance);
        self::assertNotNull($mapped);
        self::assertInstanceOf(MappedClass::class, $mapped);
        self::assertSame($provider->getStaticMappedClass(Person::class), get_class($mapped));

        self::assertSame('person', $mapped::getRSIndexName());
        self::assertSame('document', $mapped::getRSIndexDocumentPrefix());
        self::assertCount(4, $mapped::getRSFieldsDefinition());
        self::assertEquals([
            new Builder\TextField('firstname', false, null, 'fr', true),
            new Builder\NumericField('age'),
            new Builder\TagField('skill', '|'),
            new Builder\GeoField('address'),
        ], $mapped::getRSFieldsDefinition());

        self::assertEquals([
            'firstname' => 'John',
            'age' => 30,
            'skill' => 'cooking|painting',
            'address' => '0,0',
        ], $mapped->getRSDataArray($instance));

        self::assertEquals([
            'nameSuggestion' => [
                ['value' => 'John', 'score' => 0.5, 'increment' => false, 'payload' => 'type:firstname'],
                ['value' => 'Doe', 'score' => 1.0, 'increment' => false, 'payload' => null],
            ],
            'suggestion' => [
                ['value' => 'Tokyo', 'score' => 1.0, 'increment' => false, 'payload' => null],
            ],
        ], $mapped->getRSSuggestions($instance));
        self::assertEquals('document1', $mapped->getRSDocumentId($instance));
    }

    public function testFileError()
    {
        $provider = new JsonProvider();
        $before = serialize($provider);
        $provider->addJson(__DIR__.'/notExistingFile');
        self::assertSame($before, serialize($provider));
    }

    public function testNotJsonFileError()
    {
        $this->expectException(Exception::class);
        $provider = new JsonProvider();
        $provider->addJson(__FILE__);
    }

    public function testWrongJsonContent()
    {
        $provider = new JsonProvider();
        $before = serialize($provider);
        $provider->addJson(__DIR__.'/../fixtures/json/wrongJson.json');
        self::assertSame($before, serialize($provider));
    }
}
