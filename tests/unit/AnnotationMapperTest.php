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
use MacFJA\RediSearch\Integration\Annotation\AnnotationProvider;
use MacFJA\RediSearch\Integration\Exception\InvalidClassException;
use MacFJA\RediSearch\Integration\MappedClass;
use MacFJA\RediSearch\Integration\tests\fixtures\annotation\WithAnnotation;
use MacFJA\RediSearch\Integration\tests\fixtures\withclass\Person;
use PHPUnit\Framework\TestCase;

/**
 * @covers \MacFJA\RediSearch\Integration\Annotation\AnnotationProvider
 * @covers \MacFJA\RediSearch\Integration\Annotation\TemplateAnnotationMapper
 *
 * @uses \MacFJA\RediSearch\Integration\Helper\MapperHelper
 */
class AnnotationMapperTest extends TestCase
{
    /**
     * @covers \MacFJA\RediSearch\Integration\Annotation\DocumentId
     * @covers \MacFJA\RediSearch\Integration\Annotation\GeoField
     * @covers \MacFJA\RediSearch\Integration\Annotation\Index
     * @covers \MacFJA\RediSearch\Integration\Annotation\NumericField
     * @covers \MacFJA\RediSearch\Integration\Annotation\Suggestion
     * @covers \MacFJA\RediSearch\Integration\Annotation\TagField
     * @covers \MacFJA\RediSearch\Integration\Annotation\TextField
     */
    public function testNominalCase()
    {
        $provider = new AnnotationProvider();
        $className = $provider->getStaticMappedClass(WithAnnotation::class);
        $className2 = $provider->getStaticMappedClass(WithAnnotation::class);

        self::assertNotNull($className);
        self::assertNotNull($className2);
        self::assertSame($className, $className2);

        $instance = new WithAnnotation();
        $mapped = $provider->getMappedClass($instance);
        self::assertNotNull($mapped);
        self::assertInstanceOf(MappedClass::class, $mapped);
        self::assertSame($provider->getStaticMappedClass(WithAnnotation::class), get_class($mapped));

        self::assertSame('tests_annotation', $mapped::getRSIndexName());
        self::assertSame('document-', $mapped::getRSIndexDocumentPrefix());
        self::assertCount(5, $mapped::getRSFieldsDefinition());
        self::assertEquals([
            new Builder\TextField('firstname', false, null, 'fr'),
            new Builder\NumericField('age'),
            new Builder\GeoField('gps'),
            new Builder\TextField('lastname'),
            new Builder\TagField('skills'),
        ], $mapped::getRSFieldsDefinition());

        self::assertEquals([
            'firstname' => 'John',
            'age' => 30,
            'gps' => '0,0',
            'lastname' => 'Doe',
            'skills' => 'reading, speaking',
        ], $mapped->getRSDataArray($instance));

        self::assertEquals(['name' => [
            ['value' => 'John', 'score' => 1.0, 'increment' => false, 'payload' => null],
            ['value' => 'Doe', 'score' => 1.0, 'increment' => false, 'payload' => null],
        ]], $mapped->getRSSuggestions($instance));

        self::assertEquals('document-1', $mapped->getRSDocumentId($instance));
    }

    public function testErrorCase()
    {
        $provider = new AnnotationProvider();
        $className = $provider->getStaticMappedClass(Person::class);
        $className2 = $provider->getStaticMappedClass(Person::class);

        self::assertNull($className);
        self::assertNull($className2);

        $notExistingClass = $provider->getStaticMappedClass('\\NotExisting\\Class');
        self::assertNull($notExistingClass);

        $notAClass = 1;
        $notMapped = $provider->getMappedClass($notAClass);
        self::assertNull($notMapped);
    }

    /**
     * @uses \MacFJA\RediSearch\Integration\Annotation\TextField
     * @uses \MacFJA\RediSearch\Integration\Annotation\TagField
     * @uses \MacFJA\RediSearch\Integration\Annotation\GeoField
     * @uses \MacFJA\RediSearch\Integration\Annotation\NumericField
     * @uses \MacFJA\RediSearch\Integration\Annotation\DocumentId
     * @uses \MacFJA\RediSearch\Integration\Annotation\Suggestion
     * @uses \MacFJA\RediSearch\Integration\Helper\MapperHelper
     */
    public function testMappingFor()
    {
        $provider = new AnnotationProvider();
        self::assertTrue($provider->hasMappingFor(WithAnnotation::class));
        self::assertFalse($provider->hasMappingFor('A'));
        self::assertFalse($provider->hasMappingFor(Person::class));
    }

    /**
     * @uses \MacFJA\RediSearch\Integration\Annotation\TextField
     * @uses \MacFJA\RediSearch\Integration\Annotation\TagField
     * @uses \MacFJA\RediSearch\Integration\Annotation\GeoField
     * @uses \MacFJA\RediSearch\Integration\Annotation\NumericField
     * @uses \MacFJA\RediSearch\Integration\Annotation\DocumentId
     * @uses \MacFJA\RediSearch\Integration\Annotation\Suggestion
     * @uses \MacFJA\RediSearch\Integration\Helper\MapperHelper
     * @covers \MacFJA\RediSearch\Integration\Exception\InvalidClassException
     */
    public function testWrongMappingCase1()
    {
        $provider = new AnnotationProvider();
        $instance = new WithAnnotation();
        $mapped = $provider->getMappedClass($instance);
        $wrongMapping = new Person();
        $this->expectException(InvalidClassException::class);
        $mapped->getRSDataArray($wrongMapping);
    }

    /**
     * @uses \MacFJA\RediSearch\Integration\Annotation\TextField
     * @uses \MacFJA\RediSearch\Integration\Annotation\TagField
     * @uses \MacFJA\RediSearch\Integration\Annotation\GeoField
     * @uses \MacFJA\RediSearch\Integration\Annotation\NumericField
     * @uses \MacFJA\RediSearch\Integration\Annotation\DocumentId
     * @uses \MacFJA\RediSearch\Integration\Annotation\Suggestion
     * @covers \MacFJA\RediSearch\Integration\Exception\InvalidClassException
     */
    public function testWrongMappingCase2()
    {
        $provider = new AnnotationProvider();
        $instance = new WithAnnotation();
        $mapped = $provider->getMappedClass($instance);
        $wrongMapping = new Person();
        $this->expectException(InvalidClassException::class);
        $mapped->getRSSuggestions($wrongMapping);
    }

    /**
     * @uses \MacFJA\RediSearch\Integration\Annotation\TextField
     * @uses \MacFJA\RediSearch\Integration\Annotation\TagField
     * @uses \MacFJA\RediSearch\Integration\Annotation\GeoField
     * @uses \MacFJA\RediSearch\Integration\Annotation\NumericField
     * @uses \MacFJA\RediSearch\Integration\Annotation\DocumentId
     * @uses \MacFJA\RediSearch\Integration\Annotation\Suggestion
     * @covers \MacFJA\RediSearch\Integration\Exception\InvalidClassException
     */
    public function testWrongMappingCase3()
    {
        $provider = new AnnotationProvider();
        $instance = new WithAnnotation();
        $mapped = $provider->getMappedClass($instance);
        $wrongMapping = new Person();
        $this->expectException(InvalidClassException::class);
        $mapped->getRSDocumentId($wrongMapping);
    }
}
