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

namespace MacFJA\RediSearch\Integration\Mapping;

use function array_key_exists;
use DOMAttr;
use Error;
use Exception;
use function get_class;
use InvalidArgumentException;
use function is_string;
use MacFJA\RediSearch\Redis\Command\CreateCommand\CreateCommandFieldOption;
use MacFJA\RediSearch\Redis\Command\CreateCommand\GeoFieldOption;
use MacFJA\RediSearch\Redis\Command\CreateCommand\NumericFieldOption;
use MacFJA\RediSearch\Redis\Command\CreateCommand\TagFieldOption;
use MacFJA\RediSearch\Redis\Command\CreateCommand\TextFieldOption;
use ReflectionException;
use ReflectionMethod;
use ReflectionProperty;

trait FieldMapping
{
    /**
     * @return null|mixed
     */
    protected function getFieldValue(object $object, ?string $getter, ?string $property, ?string $fieldName)
    {
        if (is_string($getter)) {
            try {
                return $this->getObjectMethod($object, $getter);
            } catch (Exception $exception) {
                // Do nothing
            }
        }
        $name = $property ?? $fieldName;
        if (is_string($name)) {
            try {
                return $this->getObjectProperty($object, $name);
            } catch (Exception $exception) {
                // Do nothing
            }
        }

        return null;
    }

    /**
     * @return mixed
     */
    protected function getObjectProperty(object $object, string $property)
    {
        $data = get_object_vars($object);
        if (array_key_exists($property, $data)) {
            return $data[$property];
        }

        try {
            $propertyReflection = new ReflectionProperty($object, $property);
            $propertyReflection->setAccessible(true);

            return $propertyReflection->getValue($object);
        } catch (ReflectionException $exception) {
            // Do nothing
        }

        throw new InvalidArgumentException(sprintf('Unable to read property %s from %s', $property, get_class($object)));
    }

    /**
     * @return mixed
     */
    protected function getObjectMethod(object $object, string $method)
    {
        try {
            return $object->{$method}();
        } catch (Error $exception) {
            // Do nothing
        }

        try {
            $propertyReflection = new ReflectionMethod($object, $method);
            $propertyReflection->setAccessible(true);

            return $propertyReflection->invoke($object);
        } catch (ReflectionException $exception) {
            // Do nothing
        }

        throw new InvalidArgumentException(sprintf('Unable to read method %s from %s', $method, get_class($object)));
    }

    /**
     * @param array<string,mixed> $options
     */
    private function getFieldOption(string $name, string $type, array $options = []): CreateCommandFieldOption
    {
        $type = strtolower($type);

        switch ($type) {
            case 'text':
                $option = new TextFieldOption();

                break;

            case 'geo':
                $option = new GeoFieldOption();

                break;

            case 'numeric':
                $option = new NumericFieldOption();

                break;

            case 'tag':
                $option = new TagFieldOption();

                break;

            default:
                throw new InvalidArgumentException('Type not supported');
        }
        $option->setField($name);
        $option = $this->setOptionValue($option, $options, 'sortable', 'setSortable', CreateCommandFieldOption::class, 'bool');
        $option = $this->setOptionValue($option, $options, 'unnormalized', 'setUnNormalizedSortable', CreateCommandFieldOption::class, 'bool');
        $option = $this->setOptionValue($option, $options, 'noindex', 'setNoIndex', CreateCommandFieldOption::class, 'bool');

        $option = $this->setOptionValue($option, $options, 'phonetic', 'setPhonetic', TextFieldOption::class, 'string');
        $option = $this->setOptionValue($option, $options, 'nostem', 'setNoStem', TextFieldOption::class, 'bool');
        $option = $this->setOptionValue($option, $options, 'weight', 'setWeight', TextFieldOption::class, 'float');

        $option = $this->setOptionValue($option, $options, 'separator', 'setSeparator', TagFieldOption::class, 'string');

        return $this->setOptionValue($option, $options, 'casesensitive', 'setCaseSensitive', TagFieldOption::class, 'bool');
    }

    /**
     * @param array<string,mixed> $options
     */
    private function setOptionValue(CreateCommandFieldOption $option, array $options, string $key, string $setter, string $class, string $cast): CreateCommandFieldOption
    {
        if ($option instanceof $class && array_key_exists($key, $options)) {
            $option->{$setter}($this->castAttribute($options[$key], $cast));
        }

        return $option;
    }

    /**
     * @param bool|DOMAttr|float|string $value
     *
     * @return bool|float|string
     */
    private function castAttribute($value, string $cast)
    {
        if ($value instanceof DOMAttr) {
            $value = $value->textContent;
        }

        switch ($cast) {
            case 'bool':
            case 'boolean':
                return 'true' === $value || true === $value;

            case 'float':
            case 'double':
                return (float) $value;

            case 'string':
            default:
                return (string) $value;
        }
    }
}
