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

namespace MacFJA\RediSearch\Integration\AnnotationAttribute;

use InvalidArgumentException;
use function is_string;
use ReflectionMethod;
use ReflectionProperty;
use Reflector;
use RuntimeException;
use function strlen;

trait NameNormalizer
{
    private function getNormalizedName(?Reflector $reflection): string
    {
        if ($reflection instanceof ReflectionMethod) {
            $methodName = $reflection->getName();
            $fieldName = preg_replace('/^(is|set|get)([A-Z])/', '$2', $methodName);

            if (!is_string($fieldName)) {
                throw new RuntimeException('The name is invalid');
            }

            if (1 === strlen($fieldName)) {
                return strtolower($fieldName);
            }
            $firstTwo = substr($fieldName, 0, 2);
            if ($firstTwo === strtoupper($firstTwo)) {
                return $fieldName;
            }

            return lcfirst($fieldName);
        }

        if ($reflection instanceof ReflectionProperty) {
            return $reflection->getName();
        }

        throw new InvalidArgumentException('$reflector must be either a \ReflectionMethod or a \ReflectionProperty');
    }

    /**
     * @param array{"meta":object,"method"?:ReflectionMethod,"property"?:ReflectionProperty} $annotationData
     */
    private function getFieldName(array $annotationData): string
    {
        /** @var null|ReflectionMethod|ReflectionProperty $reflection */
        $reflection = $annotationData['method'] ?? $annotationData['property'] ?? null;
        /** @var null|FieldAnnotationAttribute $annotation */
        $annotation = $annotationData['meta'];

        if (!$annotation instanceof FieldAnnotationAttribute) {
            throw new InvalidArgumentException('The provided Annotation/Attribute is not a Field');
        }

        return $annotation->getName() ?? $this->getNormalizedName($reflection);
    }
}
