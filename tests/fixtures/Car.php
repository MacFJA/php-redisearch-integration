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

use function is_int;
use function is_string;
use MacFJA\RediSearch\Integration\AnnotationAttribute\DocumentId;
use MacFJA\RediSearch\Integration\AnnotationAttribute\Index;
use MacFJA\RediSearch\Integration\AnnotationAttribute\NumericField;
use MacFJA\RediSearch\Integration\AnnotationAttribute\Suggestion;
use MacFJA\RediSearch\Integration\AnnotationAttribute\TagField;
use MacFJA\RediSearch\Integration\AnnotationAttribute\TextField;

/**
 * @Index(name="car", prefix="car-")
 */
class Car
{
    /**
     * @TextField(sortable=true)
     * @Suggestion(group="brand")
     *
     * @var null|string
     */
    private $model;
    /**
     * @NumericField()
     *
     * @var null|int
     */
    private $year;
    /**
     * @TextField(weight="0.7",sortable=true)
     * @Suggestion(group="brand",score="0.7")
     *
     * @var null|string
     */
    private $maker;
    /**
     * @TagField(sortable=true,unNormalized=true)
     * @Suggestion(score="0.3",increment=true)
     *
     * @var null|string
     */
    private $type;

    public function getModel(): ?string
    {
        return $this->model;
    }

    public function setModel(?string $model): Car
    {
        $this->model = $model;

        return $this;
    }

    public function getYear(): ?int
    {
        return $this->year;
    }

    public function setYear(?int $year): Car
    {
        $this->year = $year;

        return $this;
    }

    public function getMaker(): ?string
    {
        return $this->maker;
    }

    public function setMaker(?string $maker): Car
    {
        $this->maker = $maker;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): Car
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @DocumentId()
     */
    public function getIdentifier(): ?string
    {
        if (is_string($this->model) && is_string($this->maker) && is_int($this->year)) {
            return sprintf(
                'car-%s-%s-%d',
                $this->escapeText($this->maker),
                $this->escapeText($this->model),
                $this->year
            );
        }

        return null;
    }

    private function escapeText(string $text): string
    {
        return strtolower(
            iconv(
                'UTF-8',
                'ASCII//TRANSLIT//IGNORE',
                preg_replace('/[\s_-]+/', '_', $text) ?: $text
            ) ?: $text
        );
    }
}
