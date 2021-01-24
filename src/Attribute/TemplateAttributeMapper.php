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

namespace MacFJA\RediSearch\Integration\Attribute;

use function array_merge;
use function array_unique;
use function array_values;
use function class_exists;
use function count;
use function is_array;
use function is_string;
use function reset;
use MacFJA\RediSearch\Integration\Exception\InvalidClassException;
use MacFJA\RediSearch\Integration\Helper\CommonTemplateMapperMethods;
use MacFJA\RediSearch\Integration\Helper\MapperHelper;
use MacFJA\RediSearch\Integration\Helper\ReflectionHelper;
use MacFJA\RediSearch\Integration\MappedClass;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

abstract class TemplateAttributeMapper implements MappedClass
{
    use MapperHelper;
    use ReflectionHelper;
    use CommonTemplateMapperMethods;

    /** @var array<class-string,class-string> */
    protected static $classnames = [];

    public static function init(string $classname): void
    {
        if (!class_exists($classname)) {
            throw new InvalidClassException($classname);
        }
        self::$classnames[static::class] = $classname;
    }

    public static function isValid(): bool
    {
        return count(self::getRSFieldsDefinition()) > 0;
    }

    public static function getRSIndexName(): string
    {
        $reflectionClass = new ReflectionClass(self::getClass());
        $indexName = $reflectionClass->getAttributes(Index::class);
        if (1 === count($indexName)) {
            $indexName = reset($indexName);
            /** @var Index $indexName */
            return $indexName->getName();
        }

        return self::getClass();
    }

    public function getRSDataArray($instance): array
    {
        $classname = self::getClass();
        $this->validateInstance($instance, $classname);
        $reflectionClass = new ReflectionClass($instance);

        $fields = [];
        self::visitAnnotations(
            $reflectionClass,
            function (ReflectionProperty $property, FieldAttribute $annotation) use (&$fields, $instance) {
                $name = $annotation->getName() ?? $property->getName();
                $fields[$name] = $this->serializedData(
                    $this->getValue($instance, null, $property->name),
                    $annotation->getField('')
                );
            },
            function (ReflectionMethod $method, FieldAttribute $annotation) use (&$fields, $instance) {
                $name = $annotation->getName() ?? self::getterToName($method->getName());
                $fields[$name] = $this->serializedData(
                    $this->getValue($instance, $method->name, null),
                    $annotation->getField('')
                );
            }
        );

        return $fields;
    }

    public static function getRSFieldsDefinition(): array
    {
        $reflectionClass = new ReflectionClass(self::getClass());

        $fields = [];
        self::visitAnnotations(
            $reflectionClass,
            function (ReflectionProperty $property, FieldAttribute $annotation) use (&$fields) {
                $name = $annotation->getName() ?? $property->getName();
                $fields[$name] = $annotation->getField($name);
            },
            function (ReflectionMethod $method, FieldAttribute $annotation) use (&$fields) {
                $name = $annotation->getName() ?? self::getterToName($method->getName());
                $fields[$name] = $annotation->getField($name);
            }
        );

        return array_values($fields);
    }

    public function getRSSuggestions($instance): array
    {
        $classname = self::getClass();
        $this->validateInstance($instance, $classname);
        $reflectionClass = new ReflectionClass($instance);

        $suggestions = [];

        self::visitSuggestionAnnotations(
            $reflectionClass,
            function (ReflectionProperty $property, Suggestion $annotation) use ($instance, &$suggestions) {
                $instanceValue = $this->getValue($instance, null, $property->name);
                if (!is_string($instanceValue) && !is_array($instanceValue)) {
                    return;
                }

                $values = $this->suggestionValues(
                    $instanceValue,
                    Suggestion::TYPE_WORD === $annotation->getType(),
                    $annotation->getScore(),
                    $annotation->getPayload(),
                    $annotation->isIncrement()
                );
                $suggestions[$annotation->getGroup()] = array_merge($suggestions[$annotation->getGroup()] ?? [], $values);
            },
            function (ReflectionMethod $method, Suggestion $annotation) use ($instance, &$suggestions) {
                $instanceValue = $this->getValue($instance, $method->name, null);
                if (!is_string($instanceValue) && !is_array($instanceValue)) {
                    return;
                }

                $values = $this->suggestionValues(
                    $instanceValue,
                    Suggestion::TYPE_WORD === $annotation->getType(),
                    $annotation->getScore(),
                    $annotation->getPayload(),
                    $annotation->isIncrement()
                );
                $suggestions[$annotation->getGroup()] = array_merge($suggestions[$annotation->getGroup()] ?? [], $values);
            }
        );

        return $suggestions;
    }

    public function getRSDocumentId($instance): ?string
    {
        $classname = self::getClass();
        $this->validateInstance($instance, $classname);
        $reflectionClass = new ReflectionClass($instance);

        foreach ($reflectionClass->getProperties() as $property) {
            $annotations = $property->getAttributes(DocumentId::class);
            foreach ($annotations as $annotation) {
                if ($annotation instanceof DocumentId) {
                    return $this->getValue($instance, null, $property->name);
                }
            }
        }
        foreach ($reflectionClass->getMethods() as $method) {
            $annotations = $method->getAttributes(DocumentId::class);
            if ($method->getNumberOfRequiredParameters() > 0) {
                continue;
            }
            foreach ($annotations as $annotation) {
                if ($annotation instanceof FieldAttribute) {
                    return $this->getValue($instance, $method->name, null);
                }
            }
        }

        return null;
    }

    public static function getRSSuggestionGroups(): array
    {
        $classname = self::getClass();
        $reflectionClass = new ReflectionClass($classname);

        $suggestionGroups = [];

        self::visitSuggestionAnnotations(
            $reflectionClass,
            function (ReflectionProperty $property, Suggestion $annotation) use (&$suggestionGroups) {
                $suggestionGroups[] = $annotation->getGroup();
            },
            function (ReflectionMethod $method, Suggestion $annotation) use (&$suggestionGroups) {
                $suggestionGroups[] = $annotation->getGroup();
            }
        );

        return array_unique($suggestionGroups);
    }

    /**
     * @return class-string
     */
    private static function getClass(): string
    {
        return self::$classnames[static::class];
    }

    private static function visitSuggestionAnnotations(ReflectionClass $reflectionClass, callable $onProperties, callable $onMethod): void
    {
        foreach ($reflectionClass->getProperties() as $property) {
            $annotations = $property->getAttributes(Suggestion::class);
            foreach ($annotations as $annotation) {
                if ($annotation instanceof Suggestion) {
                    $onProperties($property, $annotation);
                }
            }
        }
        foreach ($reflectionClass->getMethods() as $method) {
            $annotations = $method->getAttributes(Suggestion::class);
            if ($method->getNumberOfRequiredParameters() > 0) {
                continue;
            }
            foreach ($annotations as $annotation) {
                if ($annotation instanceof Suggestion) {
                    $onMethod($method, $annotation);
                }
            }
        }
    }

    private static function visitAnnotations(ReflectionClass $reflectionClass, callable $onProperties, callable $onMethod): void
    {
        foreach ($reflectionClass->getProperties() as $property) {
            $annotations = $property->getAttributes(FieldAttribute::class, ReflectionAttribute::IS_INSTANCEOF);
            foreach ($annotations as $annotation) {
                if ($annotation instanceof FieldAttribute) {
                    $onProperties($property, $annotation);
                }
            }
        }
        foreach ($reflectionClass->getMethods() as $method) {
            $annotations = $method->getAttributes(FieldAttribute::class, ReflectionAttribute::IS_INSTANCEOF);
            if ($method->getNumberOfRequiredParameters() > 0) {
                continue;
            }
            foreach ($annotations as $annotation) {
                if ($annotation instanceof FieldAttribute) {
                    $onMethod($method, $annotation);
                }
            }
        }
    }
}
