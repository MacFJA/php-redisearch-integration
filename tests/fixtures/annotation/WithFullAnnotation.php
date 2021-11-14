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

use MacFJA\RediSearch\Integration\AnnotationAttribute\DocumentId;
use MacFJA\RediSearch\Integration\AnnotationAttribute\GeoField;
use MacFJA\RediSearch\Integration\AnnotationAttribute\Index;
use MacFJA\RediSearch\Integration\AnnotationAttribute\NumericField;
use MacFJA\RediSearch\Integration\AnnotationAttribute\Suggestion;
use MacFJA\RediSearch\Integration\AnnotationAttribute\TagField;
use MacFJA\RediSearch\Integration\AnnotationAttribute\TextField;

/**
 * @Index(name="tests_annotation", prefix="document-", stopsWords={"redis", "php"})
 */
class WithFullAnnotation
{
    /**
     * @var string
     * @TextField(phonetic="dm:fr", name="firstname", noStem=true, weight=1.5, sortable=true, unNormalized=false, noIndex=false)
     * @Suggestion(group="name",payload="user:firstname",score="1.2",increment=true)
     */
    public $firstname = 'John';

    /**
     * @NumericField(name="age", noIndex=false, unNormalized=true, sortable=true)
     *
     * @var int
     */
    public $age = 30;

    /**
     * @var string
     * @GeoField(name="gps", sortable=false, unNormalized=false, noIndex=true)
     */
    public $address = '0,0';

    /**
     * @TextField(phonetic="dm:fr", noStem=true, weight=1.5, sortable=true, unNormalized=false, noIndex=false)
     * @Suggestion(group="name",payload="user:lastname",score="0.9",increment=true)
     */
    public function getLastname(): string
    {
        return 'Doe';
    }

    /**
     * @TagField(separator="|",noIndex=false,caseSensitive=false,unNormalized=false,sortable=false,name="skill")
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
