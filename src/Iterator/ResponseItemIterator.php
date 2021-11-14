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

namespace MacFJA\RediSearch\Integration\Iterator;

use function count;
use Countable;
use Iterator;
use MacFJA\RediSearch\Redis\Client;
use MacFJA\RediSearch\Redis\Response\AggregateResponseItem;
use MacFJA\RediSearch\Redis\Response\PaginatedResponse;
use MacFJA\RediSearch\Redis\Response\SearchResponseItem;

/**
 * @implements Iterator<AggregateResponseItem|SearchResponseItem>
 */
class ResponseItemIterator implements Iterator, Countable
{
    /** @var PaginatedResponse */
    private $paginatedResult;
    /** @var int */
    private $position = 0;

    public function __construct(PaginatedResponse $paginatedResult, ?Client $client = null)
    {
        $this->paginatedResult = $paginatedResult;
        if ($client instanceof Client) {
            $this->paginatedResult->setClient($client);
        }
    }

    /**
     * @return AggregateResponseItem|SearchResponseItem
     */
    public function current()
    {
        return $this->paginatedResult->current()[$this->position];
    }

    public function next(): void
    {
        ++$this->position;
        if ($this->position > count($this->paginatedResult->current())) {
            $this->position = 0;
            $this->paginatedResult->next();
        }
    }

    public function key(): int
    {
        return $this->paginatedResult->key() * $this->paginatedResult->getPageSize() + $this->position;
    }

    public function valid(): bool
    {
        return $this->paginatedResult->valid() && $this->key() < $this->paginatedResult->getTotalCount();
    }

    public function rewind(): void
    {
        $this->position = 0;
        $this->paginatedResult->rewind();
    }

    public function count(): int
    {
        return $this->paginatedResult->getTotalCount();
    }
}
