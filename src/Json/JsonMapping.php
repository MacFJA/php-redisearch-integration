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

use MacFJA\RediSearch\Integration\Mapping;
use MacFJA\RediSearch\Integration\Mapping\FieldMapping;
use MacFJA\RediSearch\Integration\Mapping\SuggestionMapping;
use MacFJA\RediSearch\Redis\Command\CreateCommand\CreateCommandFieldOption;

class JsonMapping implements Mapping
{
    use FieldMapping;

    /**
     * @var array<string,mixed>
     */
    private $configuration;

    /**
     * @param array<string,mixed> $configuration
     */
    public function __construct(array $configuration)
    {
        $this->configuration = $configuration;
    }

    public function getIndexName(): string
    {
        return $this->configuration['index'];
    }

    public function getDocumentPrefix(): ?string
    {
        return $this->configuration['document-prefix'] ?? null;
    }

    public function getStopWords(): ?array
    {
        return $this->configuration['stop-words'] ?? null;
    }

    public function getFields(): array
    {
        $fields = $this->configuration['fields'] ?? [];

        $keys = array_map('strval', array_keys($fields));

        return array_combine($keys, array_map(function (array $fieldConfig, string $fieldName): CreateCommandFieldOption {
            return $this->getFieldOption(
                $fieldName,
                $fieldConfig['type'] ?? '',
                $fieldConfig
            );
        }, $fields, $keys)) ?: [];
    }

    public function getDocumentId(object $object): ?string
    {
        switch ($this->configuration['id']['type'] ?? '') {
            case 'getter':
                return $object->{$this->configuration['id']['name']}();

            case 'property':
                return $object->{$this->configuration['id']['name']};
        }

        return null;
    }

    public function getData(object $object): array
    {
        $fields = $this->configuration['fields'] ?? [];

        $keys = array_map('strval', array_keys($fields));

        return array_combine($keys, array_map(function ($field, $fieldName) use ($object) {
            return $this->getFieldValue(
                $object,
                $field['getter'] ?? null,
                $field['property'] ?? null,
                $fieldName
            );
        }, $fields, $keys)) ?: [];
    }

    public function getSuggestion(object $object): array
    {
        $fields = $this->configuration['suggestions'] ?? [];

        return array_map(function ($suggestionConfig) use ($object): SuggestionMapping {
            $mapping = new SuggestionMapping();
            $mapping->setData($this->getFieldValue(
                $object,
                $suggestionConfig['getter'] ?? null,
                $suggestionConfig['property'] ?? null,
                null
            ));
            $mapping->setDictionary($suggestionConfig['group'] ?? 'suggestion');
            $mapping->setPayload($suggestionConfig['payload'] ?? null);
            $mapping->setIncrement($suggestionConfig['increment'] ?? false);
            $mapping->setScore($suggestionConfig['score'] ?? null);

            return $mapping;
        }, $fields);
    }

    public function getSuggestionGroups(): array
    {
        $fields = $this->configuration['suggestions'] ?? [];

        return array_unique(array_map(static function ($suggestionConfig): string {
            return $suggestionConfig['group'] ?? 'suggestion';
        }, $fields));
    }
}
