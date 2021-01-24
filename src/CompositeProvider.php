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

namespace MacFJA\RediSearch\Integration;

use function is_string;
use MacFJA\RediSearch\Integration\Annotation\AnnotationProvider;
use MacFJA\RediSearch\Integration\Attribute\AttributeProvider;

class CompositeProvider implements MappedClassProvider
{
    /** @var array<MappedClassProvider> */
    protected $providers;

    public function __construct()
    {
        $this->providers = [new ClassProvider(), new AnnotationProvider(), new AttributeProvider()];
    }

    public function addProvider(MappedClassProvider $provider): void
    {
        $this->providers[] = $provider;
    }

    public function removeAllProviders(): void
    {
        $this->providers = [];
    }

    public function getStaticMappedClass(string $class): ?string
    {
        foreach ($this->providers as $classProvider) {
            $mapped = $classProvider->getStaticMappedClass($class);
            if (is_string($mapped)) {
                return $mapped;
            }
        }

        return null;
    }

    public function getMappedClass($instance): ?MappedClass
    {
        foreach ($this->providers as $classProvider) {
            $mapped = $classProvider->getMappedClass($instance);
            if ($mapped instanceof MappedClass) {
                return $mapped;
            }
        }

        return null;
    }

    public function hasMappingFor(string $class): bool
    {
        foreach ($this->providers as $classProvider) {
            if ($classProvider->hasMappingFor($class)) {
                return true;
            }
        }

        return false;
    }
}
