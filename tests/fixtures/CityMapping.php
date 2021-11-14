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

namespace MacFJA\RediSearch\Integration\tests\fixtures;

use function assert;
use MacFJA\RediSearch\Integration\Mapping\SuggestionMapping;
use MacFJA\RediSearch\Redis\Command\CreateCommand\GeoFieldOption;
use MacFJA\RediSearch\Redis\Command\CreateCommand\NumericFieldOption;
use MacFJA\RediSearch\Redis\Command\CreateCommand\TagFieldOption;
use MacFJA\RediSearch\Redis\Command\CreateCommand\TextFieldOption;

class CityMapping implements \MacFJA\RediSearch\Integration\Mapping
{
    public function getIndexName(): string
    {
        return 'cities';
    }

    public function getDocumentPrefix(): ?string
    {
        return 'city-';
    }

    public function getStopWords(): ?array
    {
        return ['city'];
    }

    public function getFields(): array
    {
        return [
            'name' => (new TextFieldOption())
                ->setField('name')
                ->setSortable(true)
                ->setWeight(2.0)
                ->setNoStem(false)
                ->setNoIndex(false),
            'country' => (new TextFieldOption())
                ->setField('country')
                ->setSortable(true)
                ->setWeight(0.8)
                ->setNoStem(false)
                ->setNoIndex(false),
            'population' => (new NumericFieldOption())
                ->setField('population')
                ->setNoIndex(false)
                ->setSortable(true),
            'languages' => (new TagFieldOption())
                ->setField('languages')
                ->setNoIndex(false)
                ->setSeparator('|')
                ->setSortable(false),
            'coordinate' => (new GeoFieldOption())
                ->setField('coordinate')
                ->setNoIndex(false),
        ];
    }

    public function getDocumentId(object $object): ?string
    {
        assert($object instanceof City);

        return strtolower(sprintf('city-%s-%s', $object->name, $object->country));
    }

    public function getData(object $object): array
    {
        assert($object instanceof City);

        return [
            'name' => $object->getName(),
            'country' => $object->getCountry(),
            'population' => $object->getPopulation(),
            'languages' => $object->getAllLanguages(),
            'coordinate' => $object->getGpsCoordinate(),
        ];
    }

    public function getSuggestion(object $object): array
    {
        assert($object instanceof City);

        return [
            (new SuggestionMapping())->setData($object->getName())->setScore(1.5)->setDictionary('place'),
            (new SuggestionMapping())->setData($object->getCountry())->setDictionary('place')->setPayload('country'),
        ];
    }

    public function getSuggestionGroups(): array
    {
        return ['place'];
    }
}
