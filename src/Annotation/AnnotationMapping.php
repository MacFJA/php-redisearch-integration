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

namespace MacFJA\RediSearch\Integration\Annotation;

use Doctrine\Common\Annotations\AnnotationReader;
use MacFJA\RediSearch\Integration\AnnotationAttribute\AbstractMapping;
use ReflectionClass;
use ReflectionException;
use RuntimeException;

class AnnotationMapping extends AbstractMapping
{
    /** @var AnnotationReader */
    private $reader;

    /**
     * @phpstan-param class-string $class
     *
     * @throws ReflectionException
     * @throws RuntimeException
     *
     * @codeCoverageIgnore
     */
    public function __construct(string $class)
    {
        if (!class_exists(AnnotationReader::class)) {
            throw new RuntimeException('doctrine/annotations dependency is missing');
        }
        parent::__construct($class);
        $this->reader = new AnnotationReader();
    }

    protected function getMethodsMeta(ReflectionClass $class, string $type): array
    {
        $annotations = [];
        foreach ($class->getMethods() as $reflectionMethod) {
            $methods = array_filter(
                $this->reader->getMethodAnnotations($reflectionMethod),
                static function ($annotation) use ($type) { return $annotation instanceof $type; }
            );
            /** @noinspection SlowArrayOperationsInLoopInspection */
            $annotations = array_merge($annotations, array_map(static function ($annotation) use ($reflectionMethod) {
                return ['method' => $reflectionMethod, 'meta' => $annotation];
            }, $methods));
        }

        return $annotations;
    }

    protected function getPropertiesMeta(ReflectionClass $class, string $type): array
    {
        $annotations = [];
        foreach ($class->getProperties() as $reflectionProperty) {
            $properties = array_filter(
                $this->reader->getPropertyAnnotations($reflectionProperty),
                static function ($annotation) use ($type) { return $annotation instanceof $type; }
            );
            /** @noinspection SlowArrayOperationsInLoopInspection */
            $annotations = array_merge($annotations, array_map(static function ($annotation) use ($reflectionProperty) {
                return ['property' => $reflectionProperty, 'meta' => $annotation];
            }, $properties));
        }

        return $annotations;
    }

    protected function getClassMeta(ReflectionClass $class, string $type): array
    {
        return array_filter(
            $this->reader->getClassAnnotations($class),
            static function ($annotation) use ($type) { return $annotation instanceof $type; }
        );
    }
}
