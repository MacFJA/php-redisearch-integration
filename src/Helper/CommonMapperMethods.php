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

use function array_key_exists;
use function class_exists;
use function get_class;
use function is_object;
use function is_string;
use MacFJA\RediSearch\Integration\MappedClass;

trait CommonMapperMethods
{
    /** @var array<class-string,MappedClass|null> */
    protected $mapped = [];

    public function getStaticMappedClass(string $class): ?string
    {
        if (!class_exists($class)) {
            return null;
        }

        $mapped = $this->mapped[$class] ?? null;
        if (null === $mapped) {
            return null;
        }

        return get_class($mapped);
    }

    public function getMappedClass($instance): ?MappedClass
    {
        $class = null;
        if (is_string($instance) && class_exists($instance)) {
            $class = $instance;
        }
        if (is_object($instance)) {
            $class = get_class($instance);
        }

        if (null === $class) {
            return null;
        }

        return $this->mapped[$class] ?? null;
    }

    public function hasMappingFor(string $class): bool
    {
        return array_key_exists($class, $this->mapped) && $this->mapped[$class] instanceof MappedClass;
    }

    protected function addMapping(string $class, MappedClass $mapped): void
    {
        if (!class_exists($class)) {
            return;
        }
        if (array_key_exists($class, $this->mapped) && $this->mapped[$class] instanceof MappedClass) {
            return;
        }
        $this->mapped[$class] = $mapped;
    }
}
