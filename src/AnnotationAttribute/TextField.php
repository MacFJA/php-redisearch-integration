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

namespace MacFJA\RediSearch\Integration\AnnotationAttribute;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;
use MacFJA\RediSearch\Redis\Command\CreateCommand\CreateCommandFieldOption;
use MacFJA\RediSearch\Redis\Command\CreateCommand\TextFieldOption;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD"})
 * @NamedArgumentConstructor
 */
#[Attribute(Attribute::TARGET_METHOD|Attribute::TARGET_PROPERTY|Attribute::IS_REPEATABLE)]
class TextField implements FieldAnnotationAttribute
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
    /**
     * @var bool
     */
    private $unNormalized;

    public function __construct(?string $name = null, bool $noStem = false, ?float $weight = null, ?string $phonetic = null, bool $sortable = false, bool $noIndex = false, bool $unNormalized = false)
    {
        $this->noStem = $noStem;
        $this->weight = $weight;
        $this->phonetic = $phonetic;
        $this->sortable = $sortable;
        $this->noIndex = $noIndex;
        $this->name = $name;
        $this->unNormalized = $unNormalized;
    }

    public function getField(string $name): CreateCommandFieldOption
    {
        return (new TextFieldOption())
            ->setField($name)
            ->setNoIndex($this->noIndex)
            ->setSortable($this->sortable)
            ->setUnNormalizedSortable($this->unNormalized)
            ->setWeight($this->weight)
            ->setPhonetic($this->phonetic)
            ->setNoStem($this->noStem)
        ;
    }

    public function getName(): ?string
    {
        return $this->name;
    }
}
