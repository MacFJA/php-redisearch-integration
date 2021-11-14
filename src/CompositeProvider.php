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

use BadMethodCallException;
use Exception;
use function get_class;
use function is_string;
use function strlen;

class CompositeProvider implements MappingProvider
{
    /** @var array<MappingProvider> */
    private $providers;

    /**
     * @param string       $name
     * @param array<mixed> $arguments
     */
    public function __call($name, $arguments): void
    {
        if (strlen($name) > 3 && 0 === strpos($name, 'add') && $name[3] === strtoupper($name[3])) {
            foreach ($this->providers as $provider) {
                if (method_exists($provider, $name)) {
                    try {
                        $provider->{$name}(...$arguments);

                        return;
                    } catch (Exception $e) {
                        // Do nothing
                    }
                }
            }
        }

        throw new BadMethodCallException('Method "'.$name.'" does not exists.');
    }

    public function addProvider(MappingProvider $provider, bool $prepend = false): void
    {
        if (true === $prepend) {
            array_unshift($this->providers, $provider);

            return;
        }
        $this->providers[] = $provider;
    }

    public function getMapping($instance): ?Mapping
    {
        $class = is_string($instance) ? $instance : get_class($instance);
        foreach ($this->providers as $provider) {
            if ($provider->hasMappingFor($class)) {
                return $provider->getMapping($instance);
            }
        }

        return null;
    }

    public function hasMappingFor(string $class): bool
    {
        foreach ($this->providers as $provider) {
            if ($provider->hasMappingFor($class)) {
                return true;
            }
        }

        return false;
    }
}
