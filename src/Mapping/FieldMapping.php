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
use InvalidArgumentException;
use function is_string;
use MacFJA\RediSearch\Redis\Command\CreateCommand\CreateCommandFieldOption;
use MacFJA\RediSearch\Redis\Command\CreateCommand\GeoFieldOption;
use MacFJA\RediSearch\Redis\Command\CreateCommand\NumericFieldOption;
use MacFJA\RediSearch\Redis\Command\CreateCommand\TagFieldOption;
use MacFJA\RediSearch\Redis\Command\CreateCommand\TextFieldOption;

trait FieldMapping
{
    /**
     * @return null|mixed
     */
    protected function getFieldValue(object $object, ?string $getter, ?string $property, ?string $fieldName)
    {
        if (is_string($getter) && method_exists($object, $getter)) {
            return $object->{$getter}();
        }
        if (is_string($property) && property_exists($object, $property)) {
            return $object->{$property};
        }
        if (is_string($fieldName) && property_exists($object, $fieldName)) {
            return $object->{$fieldName};
        }

        return null;
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
        $option = $this->setOptionValue($option, $options, 'noindex', 'setNoIndex', CreateCommandFieldOption::class, 'bool');
        $option = $this->setOptionValue($option, $options, 'phonetic', 'setPhonetic', TextFieldOption::class, 'string');
        $option = $this->setOptionValue($option, $options, 'nostem', 'setNoStem', TextFieldOption::class, 'bool');
        $option = $this->setOptionValue($option, $options, 'weight', 'setWeight', TextFieldOption::class, 'float');

        return $this->setOptionValue($option, $options, 'separator', 'setSeparator', TagFieldOption::class, 'string');
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
     * @return bool|float|string
     */
    private function castAttribute(DOMAttr $value, string $cast)
    {
        switch ($cast) {
            case 'bool':
            case 'boolean':
                return 'true' === $value->textContent;

            case 'float':
            case 'double':
                return (float) $value->textContent;

            case 'string':
            default:
                return $value->textContent;
        }
    }
}
