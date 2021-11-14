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

/**
 * @codeCoverageIgnore Value object
 */
class AddingSuggestionEvent implements Event
{
    /** @var string */
    private $group;

    /** @var string */
    private $suggestion;

    /** @var null|float */
    private $score;

    /** @var bool */
    private $increment;

    /** @var null|string */
    private $payload;

    /** @var object */
    private $instance;

    public function __construct(string $group, string $suggestion, ?float $score, bool $increment, ?string $payload, object $instance)
    {
        $this->group = $group;
        $this->suggestion = $suggestion;
        $this->score = $score;
        $this->increment = $increment;
        $this->payload = $payload;
        $this->instance = $instance;
    }

    public function getGroup(): string
    {
        return $this->group;
    }

    public function getSuggestion(): string
    {
        return $this->suggestion;
    }

    public function getScore(): ?float
    {
        return $this->score;
    }

    public function isIncrement(): bool
    {
        return $this->increment;
    }

    public function getPayload(): ?string
    {
        return $this->payload;
    }

    public function getInstance(): object
    {
        return $this->instance;
    }
}
