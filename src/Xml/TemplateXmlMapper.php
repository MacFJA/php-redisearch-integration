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

use function array_merge;
use function array_unique;
use function assert;
use function count;
use function property_exists;
use function reset;
use function settype;
use function sprintf;
use MacFJA\RediSearch\Index\Builder\Field;
use MacFJA\RediSearch\Index\Builder\GeoField;
use MacFJA\RediSearch\Index\Builder\NumericField;
use MacFJA\RediSearch\Index\Builder\TagField;
use MacFJA\RediSearch\Index\Builder\TextField;
use MacFJA\RediSearch\Integration\Exception\DuplicateFieldDefinitionException;
use MacFJA\RediSearch\Integration\Exception\UnknownFieldDefinitionException;
use MacFJA\RediSearch\Integration\Helper\CommonTemplateMapperMethods;
use MacFJA\RediSearch\Integration\Helper\MapperHelper;
use MacFJA\RediSearch\Integration\Helper\ReflectionHelper;
use MacFJA\RediSearch\Integration\MappedClass;
use SimpleXMLElement;

/**
 * @phan-file-suppress PhanPartialTypeMismatchArgument
 */
abstract class TemplateXmlMapper implements MappedClass
{
    use MapperHelper;
    use ReflectionHelper;
    use CommonTemplateMapperMethods;

    /** @var array<string,SimpleXMLElement> */
    protected static $xmlData = [];

    public static function init(SimpleXMLElement $xmlData): void
    {
        self::$xmlData[static::class] = $xmlData;
    }

    public static function getRSIndexName(): string
    {
        $data = self::getXmlData();

        return (string) ($data['indexname'] ?? $data['name']);
    }

    public function getRSDataArray($instance): array
    {
        $data = self::getXmlData();
        $classname = (string) $data['name'];
        $this->validateInstance($instance, $classname);

        $fields = [];
        foreach ($data->fields->children() as $field) {
            $name = (string) $field;
            $value = $this->serializedData(
                $this->getValue(
                    $instance,
                    self::castAttributeOrNull($field, 'getter'),
                    self::castAttributeOrNull($field, 'property'),
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
        $data = self::getXmlData();

        $fields = [];
        foreach ($data->fields->children() as $field) {
            $name = (string) $field;
            $fields[] = self::getFieldDefinition($name);
        }

        return $fields;
    }

    public function getRSSuggestions($instance): array
    {
        $data = self::getXmlData();
        $classname = (string) $data['name'];
        $this->validateInstance($instance, $classname);

        $suggestions = [];
        foreach ($data->suggestions->children() ?? [] as $suggestion) {
            $getter = 'getter' === $suggestion->getName() ? self::castAttributeOrNull($suggestion, 'name') : null;
            $property = 'property' === $suggestion->getName() ? self::castAttributeOrNull($suggestion, 'name') : null;
            $value = $this->getValue($instance, $getter, $property, null);
            if (null === $value) {
                continue;
            }

            $groupSuggestions = $this->suggestionValues(
                $value,
                'word' === (self::castAttributeOrNull($suggestion, 'type') ?? 'full'),
                self::castAttributeOrNull($suggestion, 'score', 'double') ?? 1.0,
                self::castAttributeOrNull($suggestion, 'payload'),
                'true' === (self::castAttributeOrNull($suggestion, 'increment') ?? 'false')
            );
            $group = self::castAttributeOrNull($suggestion, 'group') ?? 'suggestion';

            $suggestions[(string) $group] = array_merge($suggestions[(string) $group] ?? [], $groupSuggestions);
        }

        return $suggestions;
    }

    public function getRSDocumentId($instance): ?string
    {
        $data = self::getXmlData();
        $classname = (string) $data['name'];
        $this->validateInstance($instance, $classname);
        if (!property_exists($data, 'id')) {
            return null;
        }
        $documentId = $data->id;
        $type = (string) $documentId['type'];
        $getter = 'getter' === $type ? (string) $documentId : null;
        $property = 'property' === $type ? (string) $documentId : null;

        return $this->getValue($instance, $getter, $property, null);
    }

    public static function getRSSuggestionGroups(): array
    {
        $data = self::getXmlData();

        $suggestionGroups = [];
        foreach ($data->suggestions->children() ?? [] as $suggestion) {
            $suggestionGroups[] = (string) (self::castAttributeOrNull($suggestion, 'group') ?? 'suggestion');
        }

        return array_unique($suggestionGroups);
    }

    /**
     * @throws UnknownFieldDefinitionException
     * @throws DuplicateFieldDefinitionException
     */
    private static function getFieldDefinition(string $name): Field
    {
        $data = self::getXmlData();
        $nodes = $data->xpath(sprintf('//fields/*[text()="%s"]', $name));
        if (false === $nodes || 0 === count($nodes)) {
            throw new UnknownFieldDefinitionException((string) $data['name'], $name);
        }
        if (count($nodes) > 1) {
            throw new DuplicateFieldDefinitionException((string) $data['name'], $name);
        }
        $field = reset($nodes);
        assert($field instanceof SimpleXMLElement);
        switch ($field->getName()) {
            case 'text-field':
                return new TextField(
                    $name,
                    'true' === ((string) ($field['nostem'] ?? 'false')),
                    self::castAttributeOrNull($field, 'weight', 'double'),
                    self::castAttributeOrNull($field, 'phonetic'),
                    'true' === ((string) ($field['sortable'] ?? 'false')),
                    'true' === ((string) ($field['noindex'] ?? 'false'))
                );
            case 'numeric-field':
                return new NumericField(
                    $name,
                    'true' === ((string) ($field['sortable'] ?? 'false')),
                    'true' === ((string) ($field['noindex'] ?? 'false'))
                );
            case 'tag-field':
                return new TagField(
                    $name,
                    self::castAttributeOrNull($field, 'separator'),
                    'true' === ((string) ($field['sortable'] ?? 'false')),
                    'true' === ((string) ($field['noindex'] ?? 'false'))
                );
            case 'geo-field':
                return new GeoField(
                    $name,
                    'true' === ((string) ($field['noindex'] ?? 'false'))
                );
        }

        throw new UnknownFieldDefinitionException((string) $data['name'], $name);
    }

    private static function getXmlData(): SimpleXMLElement
    {
        return self::$xmlData[static::class];
    }

    /**
     * @phpstan-param "string"|"integer"|"boolean"|"double" $cast
     * @psalm-param "string"|"integer"|"boolean"|"double" $cast
     *
     * @return null|bool|float|int|string
     * @phan-suppress PhanTypeMismatchReturn
     */
    private static function castAttributeOrNull(SimpleXMLElement $element, string $attribute, string $cast = 'string')
    {
        /** @var null|SimpleXMLElement $value */
        $value = $element[$attribute];

        if ($value instanceof SimpleXMLElement) {
            settype($value, $cast);
        }

        return $value;
    }
}
