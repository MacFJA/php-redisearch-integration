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

use InvalidArgumentException;
use MacFJA\RediSearch\Integration\AnnotationAttribute\NameNormalizer;
use ReflectionClass;
use ReflectionMethod;
use Reflector;
use RuntimeException;

/**
 * @covers \MacFJA\RediSearch\Integration\AnnotationAttribute\NameNormalizer
 *
 * @internal
 */
class NameNormalizerTest extends \PHPUnit\Framework\TestCase
{
    /** @var object */
    private $normalizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->normalizer = new class() {
            use NameNormalizer;

            public function getNormalized(?Reflector $reflection): string
            {
                return $this->getNormalizedName($reflection);
            }
        };
    }

    public function testMissingReflector(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('$reflector must be either a \ReflectionMethod or a \ReflectionProperty');
        $this->normalizer->getNormalized(null);
    }

    public function testWrongReflector(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('$reflector must be either a \ReflectionMethod or a \ReflectionProperty');
        $this->normalizer->getNormalized(new ReflectionClass($this));
    }

    public function testNotAGetter(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The name is invalid');

        $this->normalizer->getNormalized(new ReflectionMethod($this, 'testNotAGetter'));
    }

    public function testGetter(): void
    {
        $object = new class() {
            public function getX(): int
            {
                return 10;
            }

            public function getURL(): string
            {
                return 'https://google.com/';
            }

            public function getName(): string
            {
                return 'John';
            }

            public function getLastName(): string
            {
                return 'Doe';
            }
        };

        static::assertSame('x', $this->normalizer->getNormalized(new ReflectionMethod($object, 'getX')));
        static::assertSame('URL', $this->normalizer->getNormalized(new ReflectionMethod($object, 'getURL')));
        static::assertSame('name', $this->normalizer->getNormalized(new ReflectionMethod($object, 'getName')));
        static::assertSame('lastName', $this->normalizer->getNormalized(new ReflectionMethod($object, 'getLastName')));
    }

    public function testGetterIs(): void
    {
        $object = new class() {
            public function isX(): bool
            {
                return true;
            }

            public function isURL(): bool
            {
                return false;
            }

            public function isName(): bool
            {
                return true;
            }

            public function isLastName(): bool
            {
                return true;
            }
        };

        static::assertSame('x', $this->normalizer->getNormalized(new ReflectionMethod($object, 'isX')));
        static::assertSame('URL', $this->normalizer->getNormalized(new ReflectionMethod($object, 'isURL')));
        static::assertSame('name', $this->normalizer->getNormalized(new ReflectionMethod($object, 'isName')));
        static::assertSame('lastName', $this->normalizer->getNormalized(new ReflectionMethod($object, 'isLastName')));
    }
}
