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

use MacFJA\RediSearch\Integration\Annotation\AnnotationMapping;
use MacFJA\RediSearch\Integration\tests\fixtures\annotation\WithAnnotation;
use MacFJA\RediSearch\Integration\tests\fixtures\annotation\WithFullAnnotation;
use MacFJA\RediSearch\Integration\tests\fixtures\annotation\WithMinimalAnnotation;
use MacFJA\RediSearch\Redis\Command\CreateCommand\GeoFieldOption;
use MacFJA\RediSearch\Redis\Command\CreateCommand\NumericFieldOption;
use MacFJA\RediSearch\Redis\Command\CreateCommand\TagFieldOption;
use MacFJA\RediSearch\Redis\Command\CreateCommand\TextFieldOption;
use ReflectionException;

/**
 * @covers \MacFJA\RediSearch\Integration\Annotation\AnnotationMapping
 * @covers \MacFJA\RediSearch\Integration\AnnotationAttribute\AbstractMapping
 * @covers \MacFJA\RediSearch\Integration\AnnotationAttribute\GeoField
 * @covers \MacFJA\RediSearch\Integration\AnnotationAttribute\Index
 * @covers \MacFJA\RediSearch\Integration\AnnotationAttribute\NumericField
 * @covers \MacFJA\RediSearch\Integration\AnnotationAttribute\Suggestion
 * @covers \MacFJA\RediSearch\Integration\AnnotationAttribute\TagField
 * @covers \MacFJA\RediSearch\Integration\AnnotationAttribute\TextField
 *
 * @internal
 */
class AnnotationMappingTest extends \PHPUnit\Framework\TestCase
{
    public function testConstructorInvalidClass(): void
    {
        $this->expectException(ReflectionException::class);
        // @phpstan-ignore-next-line
        new AnnotationMapping('\MacFJA\RediSearch\Integration\tests\NotExistingClass');
    }

    public function testGetIndexNameNoAnnotation(): void
    {
        $annotation = new AnnotationMapping(self::class);
        static::assertSame(self::class, $annotation->getIndexName());
    }

    public function testGetIndexName(): void
    {
        $annotation = new AnnotationMapping(WithAnnotation::class);
        static::assertSame('tests_annotation', $annotation->getIndexName());
    }

    public function testGetDocumentPrefix(): void
    {
        $annotation = new AnnotationMapping(WithMinimalAnnotation::class);
        static::assertNull($annotation->getDocumentPrefix());

        $annotation = new AnnotationMapping(WithFullAnnotation::class);
        static::assertSame('document-', $annotation->getDocumentPrefix());

        $annotation = new AnnotationMapping(self::class);
        static::assertNull($annotation->getDocumentPrefix());
    }

    public function testGetStopsWords(): void
    {
        $annotation = new AnnotationMapping(WithMinimalAnnotation::class);
        static::assertNull($annotation->getStopWords());

        $annotation = new AnnotationMapping(WithFullAnnotation::class);
        static::assertSame(['redis', 'php'], $annotation->getStopWords());

        $annotation = new AnnotationMapping(self::class);
        static::assertNull($annotation->getStopWords());
    }

    public function testGetSuggestionGroups(): void
    {
        $annotation = new AnnotationMapping(WithMinimalAnnotation::class);
        static::assertEmpty($annotation->getSuggestionGroups());

        $annotation = new AnnotationMapping(WithFullAnnotation::class);
        static::assertSame(['name'], $annotation->getSuggestionGroups());

        $annotation = new AnnotationMapping(self::class);
        static::assertEmpty($annotation->getSuggestionGroups());
    }

    public function testGetDocumentId(): void
    {
        $annotation = new AnnotationMapping(WithMinimalAnnotation::class);
        $object = new WithMinimalAnnotation();
        static::assertNull($annotation->getDocumentId($object));

        $annotation = new AnnotationMapping(WithFullAnnotation::class);
        $object = new WithFullAnnotation();
        static::assertSame('document-1', $annotation->getDocumentId($object));

        $annotation = new AnnotationMapping(self::class);
        static::assertNull($annotation->getDocumentId($this));
    }

    public function testGetData(): void
    {
        $annotation = new AnnotationMapping(WithFullAnnotation::class);
        $object = new WithFullAnnotation();
        static::assertSame([
            'firstname' => 'John',
            'age' => 30,
            'gps' => '0,0',
            'lastname' => 'Doe',
            'skill' => [
                0 => 'reading',
                1 => 'speaking',
            ],
        ], $annotation->getData($object));
    }

    public function testGetFields(): void
    {
        $annotation = new AnnotationMapping(WithFullAnnotation::class);
        static::assertEquals([
            'firstname' => (new TextFieldOption())->setField('firstname')->setPhonetic('dm:fr')->setNoStem(true)->setWeight(1.5)->setSortable(true)->setUnNormalizedSortable(false)->setNoIndex(false),
            'age' => (new NumericFieldOption())->setField('age')->setUnNormalizedSortable(true)->setSortable(true),
            'gps' => (new GeoFieldOption())->setField('gps')->setNoIndex(true),
            'lastname' => (new TextFieldOption())->setField('lastname')->setNoStem(true)->setWeight(1.5)->setSortable(true)->setPhonetic('dm:fr'),
            'skill' => (new TagFieldOption())->setField('skill')->setSeparator('|'),
        ], $annotation->getFields());
    }
}
