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

use MacFJA\RediSearch\Integration\Exception\MappingNotFoundException;
use MacFJA\RediSearch\Integration\Iterator\ResponseItemIterator;
use MacFJA\RediSearch\Redis\Client;
use MacFJA\RediSearch\Redis\Command\Aggregate;
use MacFJA\RediSearch\Redis\Command\PaginatedCommand;
use MacFJA\RediSearch\Redis\Command\Search;
use MacFJA\RediSearch\Redis\Response\AggregateResponseItem;
use MacFJA\RediSearch\Redis\Response\SearchResponseItem;
use MacFJA\RediSearch\Redis\Response\SuggestionResponseItem;

interface ObjectRepository
{
    /**
     *     @throws MappingNotFoundException
     *
     * @return array<string, array<SuggestionResponseItem>>
     */
    public function getSuggestions(string $classname, string $prefix, ?string $inGroup = null): array;

    /**
     * @throws MappingNotFoundException
     */
    public function getSearchCommand(string $classname): Search;

    /**
     * @throws MappingNotFoundException
     */
    public function getAggregateCommand(string $classname): Aggregate;

    /**
     * @return array<AggregateResponseItem|SearchResponseItem>
     */
    public function getAllPaginatedResponseArray(PaginatedCommand $paginatedCommand): array;

    /**
     * @return ResponseItemIterator<AggregateResponseItem|SearchResponseItem>
     */
    public static function getAllPaginatedResponseIterator(PaginatedCommand $paginatedCommand, Client $client): ResponseItemIterator;

    /**
     * @param array<string> $fields
     *
     *     @throws MappingNotFoundException
     *
     * @return array<string,array<string, int>>
     */
    public function getFacets(string $classname, string $query, array $fields): array;
}
