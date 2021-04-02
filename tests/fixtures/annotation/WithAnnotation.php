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

namespace MacFJA\RediSearch\Integration\tests\fixtures\annotation;

use MacFJA\RediSearch\Integration\Annotation\DocumentId;
use MacFJA\RediSearch\Integration\Annotation\GeoField;
use MacFJA\RediSearch\Integration\Annotation\Index;
use MacFJA\RediSearch\Integration\Annotation\NumericField;
use MacFJA\RediSearch\Integration\Annotation\Suggestion;
use MacFJA\RediSearch\Integration\Annotation\TagField;
use MacFJA\RediSearch\Integration\Annotation\TextField;

/**
 * @Index(name="tests_annotation", prefix="document-")
 */
class WithAnnotation
{
    /**
     * @TextField(phonetic="fr")
     * @Suggestion(group="name")
     */
    public $firstname = 'John';

    /**
     * @NumericField
     *
     * @var int
     */
    public $age = 30;

    /**
     * @var string
     * @GeoField(name="gps")
     */
    public $address = '0,0';

    /**
     * @TextField(name="lastname")
     * @Suggestion(group="name")
     */
    public function getLastname()
    {
        return 'Doe';
    }

    /**
     * @TagField
     *
     * @return array<string>
     */
    public function getSkills(): array
    {
        return ['reading', 'speaking'];
    }

    /**
     * @DocumentId
     */
    public function getSearchId(): string
    {
        return 'document-1';
    }
}
