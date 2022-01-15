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

use function count;
use DOMDocument;
use DOMNode;
use DOMNodeList;
use DOMXPath;
use EmptyIterator;
use function is_array;
use IteratorIterator;
use MacFJA\RediSearch\Integration\AnnotationAttribute\TagField;
use MacFJA\RediSearch\Integration\Mapping;
use MacFJA\RediSearch\Integration\Mapping\FieldMapping;
use MacFJA\RediSearch\Integration\Mapping\SuggestionMapping;
use RuntimeException;

class XmlMapping implements Mapping
{
    use FieldMapping;

    /** @var DOMDocument */
    private $configuration;

    public function __construct(DOMDocument $configuration)
    {
        $this->configuration = $configuration;
    }

    public function getIndexName(): string
    {
        $value = $this->xpathAsString('/class/@indexname') ??
            $this->xpathAsString('/class/@name');
        if (null === $value) {
            throw new RuntimeException('Misconfigured mapping');
        }

        return $value;
    }

    public function getDocumentPrefix(): ?string
    {
        return $this->xpathAsString('/class/@documentprefix');
    }

    public function getStopWords(): ?array
    {
        if (!$this->xpathExists('/class/stops-words')) {
            return null;
        }

        return $this->xpathAsStringArray('/class/stops-words/word');
    }

    public function getFields(): array
    {
        $fields = [];
        $xpath = $this->runArrayXPath('/class/fields/*');
        /** @var DOMNode $xpathField */
        foreach ($xpath as $xpathField) {
            $fields[$xpathField->textContent] = $this->getFieldOption(
                $xpathField->textContent,
                str_replace('-field', '', $xpathField->localName),
                iterator_to_array(new IteratorIterator($xpathField->attributes ?? new EmptyIterator()))
            );
        }

        return $fields;
    }

    public function getDocumentId(object $object): ?string
    {
        $documentId = $this->xpathAsString('/class/id');
        if (null === $documentId) {
            return null;
        }
        $type = $this->xpathAsString('/class/id/@type');
        $getter = 'getter' === $type ? (string) $documentId : null;
        $property = 'property' === $type ? (string) $documentId : null;

        return $this->getFieldValue($object, $getter, $property, null);
    }

    public function getData(object $object): array
    {
        $fields = [];
        $xpath = $this->runArrayXPath('/class/fields/*');
        /** @var DOMNode $xpathField */
        foreach ($xpath as $xpathField) {
            $method = $this->attributeStringOrNull($xpathField, 'getter');
            $property = $this->attributeStringOrNull($xpathField, 'property');
            $fieldName = $xpathField->textContent;
            $value = $this->getFieldValue($object, $method, $property, $fieldName);
            if (is_array($value) && 'tag-field' === $xpathField->localName) {
                $value = implode($this->attributeStringOrNull($xpathField, 'separator') ?? TagField::DEFAULT_SEPARATOR, $value);
            }
            $fields[$fieldName] = $value;
        }

        return $fields;
    }

    public function getSuggestion(object $object): array
    {
        $suggestions = [];
        $xpath = $this->runArrayXPath('/class/suggestions/*');
        /** @var DOMNode $xpathSuggestion */
        foreach ($xpath as $xpathSuggestion) {
            $suggestion = new SuggestionMapping();
            $suggestion->setScore($this->attributeFloatOrNull($xpathSuggestion, 'score'));
            $suggestion->setDictionary($this->attributeStringOrNull($xpathSuggestion, 'group'));
            $suggestion->setPayload($this->attributeStringOrNull($xpathSuggestion, 'payload'));
            $suggestion->setIncrement('true' === ($this->attributeStringOrNull($xpathSuggestion, 'score') ?? '0'));
            $method = 'getter' === $xpathSuggestion->localName ? $this->attributeStringOrNull($xpathSuggestion, 'name') : null;
            $property = 'property' === $xpathSuggestion->localName ? $this->attributeStringOrNull($xpathSuggestion, 'name') : null;
            $suggestion->setData(
                $this->getFieldValue($object, $method, $property, null)
            );
            $suggestions[] = $suggestion;
        }

        return $suggestions;
    }

    public function getSuggestionGroups(): array
    {
        $definedGroups = $this->xpathAsStringArray('/class/suggestions/*/@group');
        $allSuggestions = (int) $this->xpathAsString('count(/class/suggestions/*)');

        if (count($definedGroups) < $allSuggestions) {
            $definedGroups[] = 'suggestion';
        }

        return array_unique($definedGroups);
    }

    private function xpathAsString(string $xpath): ?string
    {
        $result = $this->xpathAsStringArray($xpath);

        if (0 === count($result)) {
            return null;
        }

        if (1 === count($result)) {
            return reset($result);
        }

        return null;
    }

    /**
     * @return array<DOMNode>
     */
    private function runArrayXPath(string $xpath): array
    {
        $result = (new DOMXPath($this->configuration))->query($xpath);

        if (false === $result) {
            return [];
        }

        $final = [];
        for ($index = 0; $index < $result->length; ++$index) {
            $item = $result->item($index);
            if (null === $item) {
                continue;
            }
            $final[] = $item;
        }

        return $final;
    }

    /**
     * @return array<string>
     */
    private function xpathAsStringArray(string $xpath): array
    {
        $items = $this->runArrayXPath($xpath);

        return array_map(static function (DOMNode $node) {
            return $node->textContent;
        }, $items);
    }

    private function xpathExists(string $xpath): bool
    {
        $query = (new DOMXPath($this->configuration))->query($xpath);

        return $query instanceof DOMNodeList && $query->length > 0;
    }

    private function attributeStringOrNull(DOMNode $node, string $attribute): ?string
    {
        $attributes = $node->attributes;
        if (null === $attributes) {
            return null;
        }
        $attributeNode = $attributes->getNamedItem($attribute);

        if (null === $attributeNode) {
            return null;
        }

        return $attributeNode->textContent;
    }

    private function attributeFloatOrNull(DOMNode $node, string $attribute): ?float
    {
        $attributes = $node->attributes;
        if (null === $attributes) {
            return null;
        }
        $attributeNode = $attributes->getNamedItem($attribute);

        if (null === $attributeNode) {
            return null;
        }

        return (float) $attributeNode->textContent;
    }
}
