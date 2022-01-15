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

use MacFJA\RediSearch\Integration\AnnotationAttribute as RS;

#[RS\Index(name: 'simple')]
/** @RS\Index(name="simple") */
class SimpleObject
{
    /**
     * @RS\TextField(noStem=true,sortable=true,noIndex=false,unNormalized=true,weight="1.4",phonetic="dm:french")
     *
     * @var string
     */
    #[RS\TextField(noStem: true, sortable: true, noIndex: false, unNormalized: true, weight: 1.4, phonetic: 'dm:french')]
    private $f1;
    /**
     * @RS\NumericField(noIndex=true,sortable=true)
     *
     * @var int
     */
    #[RS\NumericField(noIndex: true, sortable: true)]
    private $f2;
    /**
     * @RS\TagField(caseSensitive=true,separator="*",sortable=true)
     *
     * @var array
     */
    #[RS\TagField(caseSensitive: true, sortable: true, separator: '*')]
    private $f3;

    /** @var string */
    private $f4;

    public function __construct(string $f1, int $f2, array $f3, string $f4)
    {
        $this->f1 = $f1;
        $this->f2 = $f2;
        $this->f3 = $f3;
        $this->f4 = $f4;
    }

    /** @RS\GeoField(sortable=true) */
    #[RS\GeoField(sortable: true)]
    public function getF4(): string
    {
        return $this->f4;
    }
}
