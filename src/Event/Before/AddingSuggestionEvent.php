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

namespace MacFJA\RediSearch\Integration\Event\Before;

use MacFJA\RediSearch\Integration\Event\Event;
use MacFJA\RediSearch\Integration\Mapping\SuggestionMapping;

/**
 * @codeCoverageIgnore Value object
 */
class AddingSuggestionEvent implements Event
{
    /** @var object */
    private $instance;
    /**
     * @var SuggestionMapping
     */
    private $suggestionMapping;

    public function __construct(SuggestionMapping $suggestionMapping, object $instance)
    {
        $this->instance = $instance;
        $this->suggestionMapping = $suggestionMapping;
    }

    public function getInstance(): object
    {
        return $this->instance;
    }

    public function getSuggestionMapping(): SuggestionMapping
    {
        return $this->suggestionMapping;
    }

    public function setSuggestionMapping(SuggestionMapping $suggestionMapping): void
    {
        $this->suggestionMapping = $suggestionMapping;
    }
}
