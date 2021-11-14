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

namespace MacFJA\RediSearch\Integration\Event\After;

use MacFJA\RediSearch\Integration\Event\Event;
use MacFJA\RediSearch\Redis\Response\SuggestionResponseItem;

/**
 * @codeCoverageIgnore Value object
 */
class GettingSuggestionsEvent implements Event
{
    /** @var string */
    private $prefix;

    /** @var bool */
    private $fuzzy;

    /** @var bool */
    private $withScores;

    /** @var bool */
    private $withPayloads;

    /** @var null|int */
    private $max;

    /** @var string */
    private $classname;

    /** @var null|string */
    private $inGroup;

    /** @var array<string,array<SuggestionResponseItem>> */
    private $suggestions;

    /**
     * @param array<string,array<SuggestionResponseItem>> $suggestions
     */
    public function __construct(string $prefix, bool $fuzzy, bool $withScores, bool $withPayloads, ?int $max, string $classname, ?string $inGroup, array $suggestions)
    {
        $this->prefix = $prefix;
        $this->fuzzy = $fuzzy;
        $this->withScores = $withScores;
        $this->withPayloads = $withPayloads;
        $this->max = $max;
        $this->classname = $classname;
        $this->inGroup = $inGroup;
        $this->suggestions = $suggestions;
    }

    public function getPrefix(): string
    {
        return $this->prefix;
    }

    public function isFuzzy(): bool
    {
        return $this->fuzzy;
    }

    public function isWithScores(): bool
    {
        return $this->withScores;
    }

    public function isWithPayloads(): bool
    {
        return $this->withPayloads;
    }

    public function getMax(): ?int
    {
        return $this->max;
    }

    public function getClassname(): string
    {
        return $this->classname;
    }

    public function getInGroup(): ?string
    {
        return $this->inGroup;
    }

    /**
     * @return array<string,array<SuggestionResponseItem>>
     */
    public function getSuggestions(): array
    {
        return $this->suggestions;
    }

    /**
     * @param array<string,array<SuggestionResponseItem>> $suggestions
     *
     * @return $this
     */
    public function setSuggestions(array $suggestions): GettingSuggestionsEvent
    {
        $this->suggestions = $suggestions;

        return $this;
    }
}
