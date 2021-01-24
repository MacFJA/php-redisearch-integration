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

namespace MacFJA\RediSearch\Integration\Helper;

use function is_string;
use function lcfirst;
use function strpos;
use function substr;
use ReflectionClass;
use ReflectionException;

trait ReflectionHelper
{
    /**
     * @param object $instance
     *
     * @throws ReflectionException
     *
     * @return null|mixed
     */
    protected function getValue($instance, ?string $getter, ?string $property, ?string $fieldName = null)
    {
        $reflection = new ReflectionClass($instance);
        if (is_string($getter) && $reflection->hasMethod($getter)) {
            $method = $reflection->getMethod($getter);
            if (0 === $method->getNumberOfRequiredParameters()) {
                $method->setAccessible(true);

                return $method->invoke($instance);
            }
        }

        if (is_string($property) && $reflection->hasProperty($property)) {
            $propertyReflection = $reflection->getProperty($property);
            $propertyReflection->setAccessible(true);

            return $propertyReflection->getValue($instance);
        }

        if (is_string($fieldName) && $reflection->hasProperty($fieldName)) {
            $propertyReflection = $reflection->getProperty($fieldName);
            $propertyReflection->setAccessible(true);

            return $propertyReflection->getValue($instance);
        }

        return null;
    }

    protected static function getterToName(string $getter): string
    {
        if (0 === strpos($getter, 'is')) {
            return lcfirst(substr($getter, 2) ?: $getter);
        }
        if (0 === strpos($getter, 'get')) {
            return lcfirst(substr($getter, 3) ?: $getter);
        }

        return $getter;
    }
}
