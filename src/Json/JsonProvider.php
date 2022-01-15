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

namespace MacFJA\RediSearch\Integration\Json;

use function array_key_exists;
use function function_exists;
use function get_class;
use function is_array;
use function is_string;
use MacFJA\RediSearch\Integration\Mapping;
use MacFJA\RediSearch\Integration\MappingProvider;

class JsonProvider implements MappingProvider
{
    /**
     * @var array<string,JsonMapping>
     */
    private $mappings = [];

    public function addJson(string $path): void
    {
        if (!function_exists('json_decode')) {
            return;
        }
        if (!file_exists($path)) {
            return;
        }
        $content = file_get_contents($path);
        if (!is_string($content)) {
            return;
        }
        $json = json_decode($content, true);

        if (!is_array($json)) {
            return;
        }

        foreach ($json as $mapping) {
            if (!array_key_exists('class', $mapping) || !is_string($mapping['class'])) {
                continue;
            }
            $this->mappings[$mapping['class']] = new JsonMapping($mapping);
        }
    }

    public function getMapping($instance): ?Mapping
    {
        $class = is_string($instance) ? $instance : get_class($instance);

        return $this->mappings[$class] ?? null;
    }

    public function hasMappingFor(string $class): bool
    {
        return array_key_exists($class, $this->mappings);
    }
}
