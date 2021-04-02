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

namespace MacFJA\RediSearch\Integration\tests\fixtures\withclass;

use MacFJA\RediSearch\Index\Builder\GeoField;
use MacFJA\RediSearch\Index\Builder\NumericField;
use MacFJA\RediSearch\Index\Builder\TextField;
use MacFJA\RediSearch\Integration\MappedClass;

class Person implements MappedClass
{
    private $firstname = 'John';

    private $coordinate = '0,0';

    private $age = 33;

    private $id = 1;

    public static function getRSIndexName(): string
    {
        return 'person';
    }

    public function getRSDataArray($instance): array
    {
        return [
            'firstname' => $this->firstname,
            'age' => $this->age,
            'address' => $this->coordinate,
        ];
    }

    public static function getRSFieldsDefinition(): array
    {
        return [
            new TextField('firstname', true, null, 'fr'),
            new NumericField('age'),
            new GeoField('address'),
        ];
    }

    public function getRSSuggestions($instance): array
    {
        return ['name' => [['value' => $this->firstname, 'score' => 1, 'increment' => false]]];
    }

    public function getRSDocumentId($instance): ?string
    {
        return self::getRSIndexDocumentPrefix().$this->id;
    }

    public static function getRSSuggestionGroups(): array
    {
        return ['name'];
    }

    public static function getRSIndexDocumentPrefix(): ?string
    {
        return 'document_';
    }
}
