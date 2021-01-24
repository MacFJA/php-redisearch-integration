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
use function array_keys;
use function array_merge;
use function array_unique;
use function count;
use MacFJA\RediSearch\Index\Builder\Field;
use MacFJA\RediSearch\Index\Builder\GeoField;
use MacFJA\RediSearch\Index\Builder\NumericField;
use MacFJA\RediSearch\Index\Builder\TagField;
use MacFJA\RediSearch\Index\Builder\TextField;
use MacFJA\RediSearch\Integration\Exception\UnknownFieldDefinitionException;
use MacFJA\RediSearch\Integration\Helper\CommonTemplateMapperMethods;
use MacFJA\RediSearch\Integration\Helper\MapperHelper;
use MacFJA\RediSearch\Integration\Helper\ReflectionHelper;
use MacFJA\RediSearch\Integration\MappedClass;

abstract class TemplateJsonMapper implements MappedClass
{
    use MapperHelper;
    use ReflectionHelper;
    use CommonTemplateMapperMethods;

    /** @var array<class-string,array{id:array,class:string,index:?string,fields:array,suggestions:array}> */
    protected static $jsonData = [];

    /**
     * @param array{id:array,class:string,index:?string,fields:array,suggestions:array} $jsonData
     */
    public static function init(array $jsonData): void
    {
        self::$jsonData[static::class] = $jsonData;
    }

    public static function isValid(): bool
    {
        return count(self::getRSFieldsDefinition()) > 0;
    }

    public static function getRSIndexName(): string
    {
        $data = self::getJsonData();

        return $data['index'] ?? $data['class'];
    }

    public function getRSDataArray($instance): array
    {
        $data = self::getJsonData();
        $this->validateInstance($instance, $data['class']);

        $fields = [];
        foreach ($data['fields'] as $name => $field) {
            $value = $this->serializedData(
                $this->getValue(
                    $instance,
                    $field['getter'] ?? null,
                    $field['property'] ?? null,
                    $name
                ),
                self::getFieldDefinition($name)
            );

            if (null === $value) {
                continue;
            }
            $fields[$name] = $value;
        }

        return $fields;
    }

    public static function getRSFieldsDefinition(): array
    {
        $data = self::getJsonData();

        $fields = [];
        foreach (array_keys($data['fields']) as $name) {
            $fields[] = self::getFieldDefinition((string) $name);
        }

        return $fields;
    }

    public function getRSSuggestions($instance): array
    {
        $data = self::getJsonData();
        $this->validateInstance($instance, $data['class']);

        $suggestions = [];
        foreach ($data['suggestions'] ?? [] as $suggestion) {
            $value = $this->getValue($instance, $suggestion['getter'] ?? null, $suggestion['property'] ?? null, null);
            if (null === $value) {
                continue;
            }

            $groupSuggestions = $this->suggestionValues(
                $value,
                ($suggestion['type'] ?? 'full') === 'word',
                $suggestion['score'] ?? 1.0,
                $suggestion['payload'] ?? null,
                $suggestion['increment'] ?? false
            );
            $group = (string) ($suggestion['group'] ?? 'suggestion');

            $suggestions[$group] = array_merge($suggestions[$group] ?? [], $groupSuggestions);
        }

        return $suggestions;
    }

    public function getRSDocumentId($instance): ?string
    {
        $data = self::getJsonData();
        $this->validateInstance($instance, $data['class']);

        if (!array_key_exists('id', $data)) {
            return null;
        }

        $getter = 'getter' === $data['id']['type'] ? $data['id']['name'] : null;
        $property = 'property' === $data['id']['type'] ? $data['id']['name'] : null;

        return $this->getValue($instance, $getter, $property, null);
    }

    public static function getRSSuggestionGroups(): array
    {
        $data = self::getJsonData();

        $suggestionGroups = [];
        foreach ($data['suggestions'] ?? [] as $suggestion) {
            $suggestionGroups[] = (string) ($suggestion['group'] ?? 'suggestion');
        }

        return array_unique($suggestionGroups);
    }

    /**
     * @throws UnknownFieldDefinitionException
     */
    private static function getFieldDefinition(string $name): Field
    {
        $data = self::getJsonData();

        $field = $data['fields'][$name] ?? null;

        if (null === $field) {
            throw new UnknownFieldDefinitionException($data['class'], $name);
        }
        switch ($field['type']) {
            case 'text':
                return new TextField(
                    $name,
                    $field['nostem'] ?? false,
                    $field['weight'] ?? null,
                    $field['phonetic'] ?? null,
                    $field['sortable'] ?? false,
                    $field['noindex'] ?? false
                );
            case 'numeric':
                return new NumericField(
                    $name,
                    $field['sortable'] ?? false,
                    $field['noindex'] ?? false
                );
            case 'tag':
                return new TagField(
                    $name,
                    $field['separator'] ?? null,
                    $field['sortable'] ?? false,
                    $field['noindex'] ?? false
                );
            case 'geo':
                return new GeoField(
                    $name,
                    $field['noindex'] ?? false
                );
        }

        throw new UnknownFieldDefinitionException($data['class'], $name);
    }

    /**
     * @return array{id:array,class:string,index:?string,fields:array,suggestions:array}
     */
    private static function getJsonData(): array
    {
        return self::$jsonData[static::class];
    }
}
