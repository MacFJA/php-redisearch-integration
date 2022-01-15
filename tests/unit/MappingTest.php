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
use MacFJA\RediSearch\Integration\Attribute\AttributeProvider;
use MacFJA\RediSearch\Integration\Json\JsonProvider;
use MacFJA\RediSearch\Integration\Mapping;
use MacFJA\RediSearch\Integration\tests\fixtures\mapping\SimpleMapping;
use MacFJA\RediSearch\Integration\tests\fixtures\mapping\SimpleObject;
use MacFJA\RediSearch\Integration\Xml\XmlProvider;
use MacFJA\RediSearch\Redis\Command\CreateCommand\GeoFieldOption;
use MacFJA\RediSearch\Redis\Command\CreateCommand\NumericFieldOption;
use MacFJA\RediSearch\Redis\Command\CreateCommand\TagFieldOption;
use MacFJA\RediSearch\Redis\Command\CreateCommand\TextFieldOption;
use PHPUnit\Framework\TestCase;

/**
 * @covers \MacFJA\RediSearch\Integration\Annotation\AnnotationMapping
 * @covers \MacFJA\RediSearch\Integration\AnnotationAttribute\AbstractMapping
 * @covers \MacFJA\RediSearch\Integration\AnnotationAttribute\NameNormalizer
 * @covers \MacFJA\RediSearch\Integration\Attribute\AttributeMapping
 * @covers \MacFJA\RediSearch\Integration\Json\JsonMapping
 * @covers \MacFJA\RediSearch\Integration\Xml\XmlMapping
 *
 * @uses \MacFJA\RediSearch\Integration\Json\JsonProvider
 * @uses \MacFJA\RediSearch\Integration\Xml\XmlProvider
 * @uses \MacFJA\RediSearch\Integration\Annotation\AnnotationProvider
 * @uses \MacFJA\RediSearch\Integration\Attribute\AttributeProvider
 * @uses \MacFJA\RediSearch\Integration\AnnotationAttribute\Index
 * @uses \MacFJA\RediSearch\Integration\AnnotationAttribute\GeoField
 * @uses \MacFJA\RediSearch\Integration\AnnotationAttribute\NumericField
 * @uses \MacFJA\RediSearch\Integration\AnnotationAttribute\TagField
 * @uses \MacFJA\RediSearch\Integration\AnnotationAttribute\TextField
 *
 * @internal
 */
class MappingTest extends TestCase
{
    /**
     * @dataProvider getDataProvider
     */
    public function testGetFields(Mapping $mapping): void
    {
        $actual = $mapping->getFields();
        $expected = [
            'f1' => (new TextFieldOption())->setField('f1')->setNoStem(true)->setNoIndex(false)->setSortable(true)->setUnNormalizedSortable(true)->setWeight(1.4)->setPhonetic('dm:french'),
            'f2' => (new NumericFieldOption())->setField('f2')->setNoIndex(true)->setSortable(true),
            'f3' => (new TagFieldOption())->setField('f3')->setCaseSensitive(true)->setSeparator('*')->setSortable(true),
            'f4' => (new GeoFieldOption())->setField('f4')->setSortable(true),
        ];

        static::assertEquals($expected, $actual);
    }

    /**
     * @dataProvider getDataProvider
     */
    public function testGetIndexName(Mapping $mapping): void
    {
        $expected = 'simple';

        static::assertEquals($expected, $mapping->getIndexName());
    }

    /**
     * @dataProvider getDataProvider
     */
    public function testGetData(Mapping $mapping): void
    {
        $expected = [
            'f1' => 'hello',
            'f2' => 42,
            'f3' => 'foo*bar*foobar',
            'f4' => '0,0',
        ];

        $simple = new SimpleObject('hello', 42, ['foo', 'bar', 'foobar'], '0,0');

        static::assertSame($expected, $mapping->getData($simple));
    }

    /**
     * @return array<array<null|Mapping>>
     */
    public function getDataProvider(): array
    {
        $json = new JsonProvider();
        $json->addJson(__DIR__.'/../fixtures/mapping/mapping.json');
        $xml = new XmlProvider();
        $xml->addXml(__DIR__.'/../fixtures/mapping/mapping.xml');
        $mappings = [
            [$json->getMapping(SimpleObject::class)],
            [$xml->getMapping(SimpleObject::class)],
            [new SimpleMapping()],
            [(new AnnotationProvider())->getMapping(SimpleObject::class)],
        ];
        if (version_compare(PHP_VERSION, '8.0.0') >= 0) {
            $mappings[] = [(new AttributeProvider())->getMapping(SimpleObject::class)];
        }

        return $mappings;
    }
}
