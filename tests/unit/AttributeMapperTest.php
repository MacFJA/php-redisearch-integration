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
use MacFJA\RediSearch\Integration\Attribute\AttributeProvider;
use MacFJA\RediSearch\Integration\MappedClass;
use MacFJA\RediSearch\Integration\tests\fixtures\annotation\WithAttribute;
use PHPUnit\Framework\TestCase;

class AttributeMapperTest extends TestCase
{
    public function testNominalCase()
    {
        if (\PHP_VERSION_ID < 800000) {
            self::markTestSkipped('Need PHP 8');
        }
        $provider = new AttributeProvider();
        $className = $provider->getStaticMappedClass(WithAttribute::class);
        $className2 = $provider->getStaticMappedClass(WithAttribute::class);

        self::assertNotNull($className);
        self::assertNotNull($className2);
        self::assertSame($className, $className2);

        $instance = new WithAttribute();
        $mapped = $provider->getMappedClass($instance);
        self::assertNotNull($mapped);
        self::assertInstanceOf(MappedClass::class, $mapped);
        self::assertSame($provider->getStaticMappedClass(WithAttribute::class), get_class($mapped));

        self::assertSame('tests_annotation', $mapped::getRSIndexName());
        self::assertSame('person-', $mapped::getRSIndexDocumentPrefix());
        self::assertCount(2, $mapped::getRSFieldsDefinition());
        self::assertEquals([
            new Builder\TextField('firstname', false, null, 'fr'),
            new Builder\TextField('lastname'),
        ], $mapped::getRSFieldsDefinition());

        self::assertEquals([
            'firstname' => 'John',
            'lastname' => 'Doe',
        ], $mapped->getRSDataArray($instance));

        self::assertEquals(['name' => [
            ['value' => 'John', 'score' => 1.0, 'increment' => false, 'payload' => null],
        ]], $mapped->getRSSuggestions($instance));

        self::assertEquals('person-1', $mapped->getRSDocumentId($instance));
    }
}
