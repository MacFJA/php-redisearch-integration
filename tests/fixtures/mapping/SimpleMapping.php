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

namespace MacFJA\RediSearch\Integration\tests\fixtures\mapping;

use function assert;
use MacFJA\RediSearch\Redis\Command\CreateCommand\GeoFieldOption;
use MacFJA\RediSearch\Redis\Command\CreateCommand\NumericFieldOption;
use MacFJA\RediSearch\Redis\Command\CreateCommand\TagFieldOption;
use MacFJA\RediSearch\Redis\Command\CreateCommand\TextFieldOption;
use ReflectionProperty;

class SimpleMapping implements \MacFJA\RediSearch\Integration\Mapping
{
    public function getIndexName(): string
    {
        return 'simple';
    }

    public function getDocumentPrefix(): ?string
    {
        return null;
    }

    public function getStopWords(): ?array
    {
        return null;
    }

    public function getFields(): array
    {
        return [
            'f1' => (new TextFieldOption())->setField('f1')->setNoStem(true)->setNoIndex(false)->setSortable(true)->setUnNormalizedSortable(true)->setWeight(1.4)->setPhonetic('dm:french'),
            'f2' => (new NumericFieldOption())->setField('f2')->setNoIndex(true)->setSortable(true),
            'f3' => (new TagFieldOption())->setField('f3')->setCaseSensitive(true)->setSeparator('*')->setSortable(true),
            'f4' => (new GeoFieldOption())->setField('f4')->setSortable(true),
        ];
    }

    public function getDocumentId(object $object): ?string
    {
        return null;
    }

    public function getData(object $object): array
    {
        assert($object instanceof SimpleObject);

        $f1 = new ReflectionProperty($object, 'f1');
        $f1->setAccessible(true);
        $f2 = new ReflectionProperty($object, 'f2');
        $f2->setAccessible(true);
        $f3 = new ReflectionProperty($object, 'f3');
        $f3->setAccessible(true);

        return [
            'f1' => $f1->getValue($object),
            'f2' => $f2->getValue($object),
            'f3' => implode('*', $f3->getValue($object)),
            'f4' => $object->getF4(),
        ];
    }

    public function getSuggestion(object $object): array
    {
        return [];
    }

    public function getSuggestionGroups(): array
    {
        return [];
    }
}
