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

namespace MacFJA\RediSearch\Integration;

use MacFJA\RediSearch\IndexBuilder;
use MacFJA\RediSearch\Redis\Command\AbstractCommand;
use MacFJA\RediSearch\Redis\Command\Aggregate;
use MacFJA\RediSearch\Redis\Command\IndexList;
use MacFJA\RediSearch\Redis\Command\Info;
use MacFJA\RediSearch\Redis\Command\Search;
use MacFJA\RediSearch\Redis\Command\SugAdd;
use MacFJA\RediSearch\Redis\Command\SugGet;

class SimpleObjectFactory implements ObjectFactory
{
    /** @var IndexBuilder */
    private $indexBuilder;
    /** @var IndexList */
    private $indexList;
    /**
     * @var string
     */
    private $rediSearchVersion;

    public function __construct(string $rediSearchVersion = AbstractCommand::MIN_IMPLEMENTED_VERSION)
    {
        $this->rediSearchVersion = $rediSearchVersion;
        $this->indexBuilder = new IndexBuilder();
        $this->indexList = new IndexList();
    }

    public function getIndexBuilder(): IndexBuilder
    {
        return $this->indexBuilder;
    }

    public function getIndexListCommand(): IndexList
    {
        return $this->indexList;
    }

    public function createSugAddCommand(): SugAdd
    {
        return new SugAdd($this->rediSearchVersion);
    }

    public function createIndexInfoCommand(): Info
    {
        return new Info($this->rediSearchVersion);
    }

    public function createSugGetCommand(): SugGet
    {
        return new SugGet($this->rediSearchVersion);
    }

    public function createSearchCommand(): Search
    {
        return new Search($this->rediSearchVersion);
    }

    public function createAggregateCommand(): Aggregate
    {
        return new Aggregate($this->rediSearchVersion);
    }
}
