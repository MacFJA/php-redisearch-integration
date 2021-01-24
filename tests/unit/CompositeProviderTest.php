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

use MacFJA\RediSearch\Integration\CompositeProvider;
use MacFJA\RediSearch\Integration\Json\JsonProvider;
use MacFJA\RediSearch\Integration\tests\fixtures\annotation\WithAnnotation;
use MacFJA\RediSearch\Integration\tests\fixtures\json\Person as JsonPerson;
use MacFJA\RediSearch\Integration\tests\fixtures\withclass\Person;
use PHPUnit\Framework\TestCase;

/**
 * @covers \MacFJA\RediSearch\Integration\CompositeProvider
 *
 * @uses \MacFJA\RediSearch\Integration\Annotation\TagField
 * @uses \MacFJA\RediSearch\Integration\Annotation\NumericField
 * @uses \MacFJA\RediSearch\Integration\Annotation\Suggestion
 * @uses \MacFJA\RediSearch\Integration\Annotation\TextField
 * @uses \MacFJA\RediSearch\Integration\Annotation\GeoField
 * @uses \MacFJA\RediSearch\Integration\Annotation\DocumentId
 * @uses \MacFJA\RediSearch\Integration\Annotation\Index
 * @uses \MacFJA\RediSearch\Integration\Annotation\TemplateAnnotationMapper
 * @uses \MacFJA\RediSearch\Integration\Annotation\AnnotationProvider
 * @uses \MacFJA\RediSearch\Integration\Attribute\AttributeProvider
 * @uses \MacFJA\RediSearch\Integration\ClassProvider
 * @uses \MacFJA\RediSearch\Integration\Helper\CommonMapperMethods
 * @uses \MacFJA\RediSearch\Integration\Helper\CommonDynamicMapperMethods
 * @uses \MacFJA\RediSearch\Integration\Helper\ReflectionHelper
 * @uses \MacFJA\RediSearch\Integration\Json\JsonProvider
 * @uses \MacFJA\RediSearch\Integration\Json\TemplateJsonMapper
 */
class CompositeProviderTest extends TestCase
{
    public function testHasMappingFor()
    {
        $composite = new CompositeProvider();

        self::assertTrue($composite->hasMappingFor(WithAnnotation::class));
        self::assertTrue($composite->hasMappingFor(Person::class));
        self::assertFalse($composite->hasMappingFor(JsonPerson::class));

        $jsonProvider = new JsonProvider();
        $jsonProvider->addJson(__DIR__.'/../fixtures/json/example.json');
        $composite->addProvider($jsonProvider);
        self::assertTrue($composite->hasMappingFor(JsonPerson::class));

        $composite->removeAllProviders();
        self::assertFalse($composite->hasMappingFor(WithAnnotation::class));
        self::assertFalse($composite->hasMappingFor(Person::class));
        self::assertFalse($composite->hasMappingFor(JsonPerson::class));
    }

    public function testGetStaticMappedClass()
    {
        $composite = new CompositeProvider();

        self::assertNotNull($composite->getStaticMappedClass(WithAnnotation::class));
        self::assertNotNull($composite->getStaticMappedClass(Person::class));
        self::assertSame(Person::class, $composite->getStaticMappedClass(Person::class));
        self::assertNull($composite->getStaticMappedClass(JsonPerson::class));

        $jsonProvider = new JsonProvider();
        $jsonProvider->addJson(__DIR__.'/../fixtures/json/example.json');
        $composite->addProvider($jsonProvider);
        self::assertNotNull($composite->getStaticMappedClass(JsonPerson::class));

        $composite->removeAllProviders();
        self::assertNull($composite->getStaticMappedClass(WithAnnotation::class));
        self::assertNull($composite->getStaticMappedClass(Person::class));
        self::assertNull($composite->getStaticMappedClass(JsonPerson::class));
    }

    public function testGetMappedClass()
    {
        $composite = new CompositeProvider();

        self::assertNotNull($composite->getMappedClass(new WithAnnotation()));
        $person = new Person();
        self::assertNotNull($composite->getMappedClass($person));
        self::assertSame($person, $composite->getMappedClass($person));
        self::assertNull($composite->getMappedClass(new JsonPerson()));

        $jsonProvider = new JsonProvider();
        $jsonProvider->addJson(__DIR__.'/../fixtures/json/example.json');
        $composite->addProvider($jsonProvider);
        self::assertNotNull($composite->getMappedClass(new JsonPerson()));

        $composite->removeAllProviders();
        self::assertNull($composite->getMappedClass(new WithAnnotation()));
        self::assertNull($composite->getMappedClass($person));
        self::assertNull($composite->getMappedClass(new JsonPerson()));
    }
}
