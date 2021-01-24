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

namespace MacFJA\RediSearch\Integration\Xml;

use function array_key_exists;
use function class_exists;
use function file_get_contents;
use function function_exists;
use function simplexml_load_string;
use MacFJA\RediSearch\Integration\Exception\InvalidClassException;
use MacFJA\RediSearch\Integration\Helper\CommonMapperMethods;
use MacFJA\RediSearch\Integration\MappedClassProvider;

class XmlProvider implements MappedClassProvider
{
    use CommonMapperMethods;

    public function addXml(string $file): void
    {
        if (!function_exists('simplexml_load_file')) {
            return;
        }
        $rawXml = file_get_contents($file);
        if (false === $rawXml) {
            return;
        }
        $xml = simplexml_load_string($rawXml);
        if (false === $xml) {
            return;
        }
        foreach ($xml as $definition) {
            $class = (string) $definition['name'];

            if (!class_exists($class)) {
                throw new InvalidClassException($class);
            }

            if (array_key_exists($class, $this->mapped)) {
                throw new InvalidClassException($class);
            }
            $anonymous = new class() extends TemplateXmlMapper {
            };

            $anonymous::init($definition);

            $this->addMapping($class, $anonymous);
        }
    }
}
