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
use MacFJA\RediSearch\Index\Builder;
use MacFJA\RediSearch\Integration\ClassProvider;
use MacFJA\RediSearch\Integration\MappedClass;
use MacFJA\RediSearch\Integration\tests\fixtures\annotation\WithAnnotation;
use MacFJA\RediSearch\Integration\tests\fixtures\withclass\Person;
use PHPUnit\Framework\TestCase;

/**
 * @covers \MacFJA\RediSearch\Integration\ClassProvider
 */
class ClassMapperTest extends TestCase
{
    public function testNominalCase()
    {
        $provider = new ClassProvider();
        $className = $provider->getStaticMappedClass(Person::class);
        $className2 = $provider->getStaticMappedClass(Person::class);

        self::assertNotNull($className);
        self::assertNotNull($className2);
        self::assertSame($className, $className2);

        $instance = new Person();
        $mapped = $provider->getMappedClass($instance);
        self::assertNotNull($mapped);
        self::assertInstanceOf(MappedClass::class, $mapped);
        self::assertSame($provider->getStaticMappedClass(Person::class), get_class($mapped));

        self::assertSame('person', $mapped::getRSIndexName());
        self::assertCount(3, $mapped::getRSFieldsDefinition());
        self::assertEquals([
            new Builder\TextField('firstname', true, null, 'fr'),
            new Builder\NumericField('age'),
            new Builder\GeoField('address'),
        ], $mapped::getRSFieldsDefinition());

        self::assertEquals([
            'firstname' => 'John',
            'age' => 33,
            'address' => '0,0',
        ], $mapped->getRSDataArray($instance));

        self::assertEquals(['name' => [
            ['value' => 'John', 'score' => 1, 'increment' => false],
        ]], $mapped->getRSSuggestions($instance));

        self::assertEquals('document_1', $mapped->getRSDocumentId($instance));
    }

    public function testWrongClass()
    {
        $provider = new ClassProvider();
        $className = $provider->getStaticMappedClass(WithAnnotation::class);
        $mapped = $provider->getMappedClass(new WithAnnotation());

        self::assertNull($className);
        self::assertNull($mapped);
        self::assertFalse($provider->hasMappingFor(WithAnnotation::class));
    }
}
