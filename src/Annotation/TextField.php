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

namespace MacFJA\RediSearch\Integration\Annotation;

use MacFJA\RediSearch\Index\Builder\Field;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD"})
 */
class TextField implements FieldAnnotation
{
    /** @var bool */
    private $noStem;

    /** @var null|float */
    private $weight;

    /** @var null|string */
    private $phonetic;

    /** @var bool */
    private $sortable;

    /** @var bool */
    private $noIndex;

    /** @var null|string */
    private $name;

    public function __construct(?string $name = null, bool $noStem = false, ?float $weight = null, ?string $phonetic = null, bool $sortable = false, bool $noIndex = false)
    {
        $this->noStem = $noStem;
        $this->weight = $weight;
        $this->phonetic = $phonetic;
        $this->sortable = $sortable;
        $this->noIndex = $noIndex;
        $this->name = $name;
    }

    public function getField(string $name): Field
    {
        return new \MacFJA\RediSearch\Index\Builder\TextField($name, $this->noStem, $this->weight, $this->phonetic, $this->sortable, $this->noIndex);
    }

    public function getName(): ?string
    {
        return $this->name;
    }
}
