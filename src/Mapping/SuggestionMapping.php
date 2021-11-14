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

class SuggestionMapping
{
    /** @var null|string */
    private $payload;
    /** @var null|float */
    private $score;
    /** @var null|string */
    private $dictionary;
    /** @var string */
    private $data;
    /** @var bool */
    private $increment = false;

    public function getPayload(): ?string
    {
        return $this->payload;
    }

    public function setPayload(?string $payload): SuggestionMapping
    {
        $this->payload = $payload;

        return $this;
    }

    public function getScore(): ?float
    {
        return $this->score;
    }

    public function setScore(?float $score): SuggestionMapping
    {
        $this->score = $score;

        return $this;
    }

    public function getDictionary(): ?string
    {
        return $this->dictionary;
    }

    public function setDictionary(?string $dictionary): SuggestionMapping
    {
        $this->dictionary = $dictionary;

        return $this;
    }

    public function getData(): string
    {
        return $this->data;
    }

    public function setData(string $data): SuggestionMapping
    {
        $this->data = $data;

        return $this;
    }

    public function isIncrement(): bool
    {
        return $this->increment;
    }

    public function setIncrement(bool $increment): SuggestionMapping
    {
        $this->increment = $increment;

        return $this;
    }
}
