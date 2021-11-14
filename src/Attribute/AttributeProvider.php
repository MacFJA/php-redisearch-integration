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

namespace MacFJA\RediSearch\Integration\Attribute;

use function array_key_exists;
use function count;
use function get_class;
use function is_string;
use MacFJA\RediSearch\Integration\Mapping;
use MacFJA\RediSearch\Integration\MappingProvider;
use ReflectionAttribute;

class AttributeProvider implements MappingProvider
{
    /** @var array<string,null|AttributeMapping> */
    private $mappings = [];

    public function getMapping($instance): ?Mapping
    {
        if (!class_exists(ReflectionAttribute::class)) {
            return null;
        }
        $class = is_string($instance) ? $instance : get_class($instance);
        if (!array_key_exists($class, $this->mappings) && class_exists($class)) {
            $mapping = new AttributeMapping($class);
            if (0 === count($mapping->getFields())) {
                $this->mappings[$class] = null;

                return null;
            }
            $this->mappings[$class] = $mapping;
        }

        return $this->mappings[$class] ?? null;
    }

    public function hasMappingFor(string $class): bool
    {
        return class_exists(ReflectionAttribute::class) && $this->getMapping($class) instanceof AttributeMapping;
    }
}
