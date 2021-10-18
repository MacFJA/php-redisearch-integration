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

class GettingFacetsEvent
{
    /** @var string */
    private $classname;

    /** @var string */
    private $query;

    /** @var array<string> */
    private $fields;

    /** @var array<string,array<string,int>> */
    private $facets;

    /**
     * @param array<string>                   $fields
     * @param array<string,array<string,int>> $facets
     */
    public function __construct(string $classname, string $query, array $fields, array $facets)
    {
        $this->classname = $classname;
        $this->query = $query;
        $this->fields = $fields;
        $this->facets = $facets;
    }

    public function getClassname(): string
    {
        return $this->classname;
    }

    public function getQuery(): string
    {
        return $this->query;
    }

    /**
     * @return array<string>
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * @return array<string,array<string,int>>
     */
    public function getFacets(): array
    {
        return $this->facets;
    }

    /**
     * @param array<string,array<string,int>> $facets
     */
    public function setFacets(array $facets): GettingFacetsEvent
    {
        $this->facets = $facets;

        return $this;
    }
}
