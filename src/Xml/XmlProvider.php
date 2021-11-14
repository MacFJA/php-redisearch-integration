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
use DOMDocument;
use DOMNamedNodeMap;
use DOMNode;
use DOMXPath;
use function get_class;
use function is_string;
use MacFJA\RediSearch\Integration\Mapping;
use MacFJA\RediSearch\Integration\MappingProvider;

class XmlProvider implements MappingProvider
{
    /** @var array<string,XmlMapping> */
    private $mappings = [];

    public function addXml(string $file): void
    {
        if (!class_exists(DOMDocument::class)) {
            return;
        }
        $rawXml = file_get_contents($file);
        if (false === $rawXml) {
            return;
        }
        $xml = new DOMDocument();
        $success = $xml->loadXML($rawXml);
        if (false === $success) {
            return;
        }
        /** @var DOMNode $classNode */
        foreach ((new DOMXPath($xml))->query('//class') ?: [] as $classNode) {
            $attributes = $classNode->attributes;
            if (!$attributes instanceof DOMNamedNodeMap) {
                continue;
            }
            $classnameNode = $attributes->getNamedItem('name');
            if (null === $classnameNode) {
                continue;
            }
            $class = $classnameNode->textContent;

            $document = new DOMDocument();
            $document->appendChild($document->importNode($classNode, true));
            $mapping = new XmlMapping($document);

            $this->mappings[$class] = $mapping;
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
