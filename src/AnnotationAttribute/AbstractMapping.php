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

namespace MacFJA\RediSearch\Integration\AnnotationAttribute;

use function count;
use MacFJA\RediSearch\Integration\Mapping\SuggestionMapping;
use MacFJA\RediSearch\Redis\Command\CreateCommand\CreateCommandFieldOption;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionProperty;

abstract class AbstractMapping implements \MacFJA\RediSearch\Integration\Mapping
{
    use NameNormalizer;
    use ReflectionFieldValue;
    /** @var ReflectionClass<object> */
    private $reflection;

    /**
     * @phpstan-param class-string $class
     *
     * @throws ReflectionException
     */
    public function __construct(string $class)
    {
        $this->reflection = new ReflectionClass($class);
    }

    public function getIndexName(): string
    {
        $meta = $this->getClassMeta($this->reflection, Index::class);
        if (1 === count($meta)) {
            /** @var Index $index */
            $index = reset($meta);

            return $index->getName();
        }

        return $this->reflection->getName();
    }

    public function getDocumentPrefix(): ?string
    {
        $meta = $this->getClassMeta($this->reflection, Index::class);
        if (1 === count($meta)) {
            /** @var Index $index */
            $index = reset($meta);

            return $index->getPrefix();
        }

        return null;
    }

    public function getStopWords(): ?array
    {
        $meta = $this->getClassMeta($this->reflection, Index::class);
        if (1 === count($meta)) {
            /** @var Index $index */
            $index = reset($meta);

            return $index->getStopsWords();
        }

        return null;
    }

    public function getFields(): array
    {
        $meta = array_merge(
            $this->getPropertiesMeta($this->reflection, FieldAnnotationAttribute::class),
            $this->getMethodsMeta($this->reflection, FieldAnnotationAttribute::class)
        );

        $fields = array_map(function (array $metaData) {
            /** @var FieldAnnotationAttribute */
            $fieldObject = $metaData['meta'];

            return $fieldObject->getField($this->getFieldName($metaData));
        }, $meta);

        return array_combine(
            array_map(static function (CreateCommandFieldOption $fieldOption) {
                return $fieldOption->getFieldName();
            }, $fields),
            $fields
        ) ?: [];
    }

    public function getDocumentId(object $object): ?string
    {
        $meta = array_merge(
            $this->getMethodsMeta($this->reflection, DocumentId::class),
            $this->getPropertiesMeta($this->reflection, DocumentId::class)
        );

        foreach ($meta as $metaData) {
            /** @var null|ReflectionMethod $method */
            $method = $metaData['method'] ?? null;
            /** @var null|ReflectionProperty $property */
            $property = $metaData['property'] ?? null;

            return $this->getReflectionValue($object, $method ?? $property);
        }

        return null;
    }

    public function getData(object $object): array
    {
        $fields = [];
        $meta = array_merge(
            $this->getPropertiesMeta($this->reflection, FieldAnnotationAttribute::class),
            $this->getMethodsMeta($this->reflection, FieldAnnotationAttribute::class)
        );
        foreach ($meta as $metaData) {
            /** @var null|ReflectionMethod $method */
            $method = $metaData['method'] ?? null;
            /** @var null|ReflectionProperty $property */
            $property = $metaData['property'] ?? null;

            $fields[$this->getFieldName($metaData)] = $this->getReflectionValue($object, $method ?? $property);
        }

        return $fields;
    }

    public function getSuggestion(object $object): array
    {
        $suggestions = array_merge(
            $this->getMethodsMeta($this->reflection, Suggestion::class),
            $this->getPropertiesMeta($this->reflection, Suggestion::class)
        );

        return array_map(function (array $data) use ($object) {
            /** @var Suggestion $suggestionObject */
            $suggestionObject = $data['meta'];
            $suggestion = new SuggestionMapping();
            $suggestion->setScore($suggestionObject->getScore());
            $suggestion->setIncrement($suggestionObject->isIncrement());
            $suggestion->setPayload($suggestionObject->getPayload());
            $suggestion->setDictionary($suggestionObject->getGroup());

            $suggestion->setData(
                $this->getReflectionValue($object, $data['method'] ?? $data['property'] ?? null)
            );

            return $suggestion;
        }, $suggestions);
    }

    public function getSuggestionGroups(): array
    {
        $meta = array_column(array_merge(
            $this->getPropertiesMeta($this->reflection, Suggestion::class),
            $this->getMethodsMeta($this->reflection, Suggestion::class)
        ), 'meta');
        $meta = array_map(static function (Suggestion $suggestion) {
            return $suggestion->getGroup();
        }, $meta);

        return array_unique($meta);
    }

    /**
     * @template T
     *
     * @param ReflectionClass<object> $class
     * @param class-string<T>         $type
     *
     * @return array<array{"method":ReflectionMethod,"meta":T}>
     */
    abstract protected function getMethodsMeta(ReflectionClass $class, string $type): array;

    /**
     * @template T
     *
     * @param ReflectionClass<object> $class
     * @param class-string<T>         $type
     *
     * @return array<array{"property":ReflectionProperty,"meta":T}>
     */
    abstract protected function getPropertiesMeta(ReflectionClass $class, string $type): array;

    /**
     * @template T
     *
     * @param ReflectionClass<object> $class
     * @param class-string<T>         $type
     *
     * @return array<T>
     */
    abstract protected function getClassMeta(ReflectionClass $class, string $type): array;
}
