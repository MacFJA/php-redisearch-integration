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

class City
{
    /** @var string */
    public $name;
    /** @var string */
    public $country;
    /** @var int */
    public $population;
    /** @var array{"lat":float,"lon":float} */
    private $coordinate;
    /** @var array<string> */
    private $languages;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): City
    {
        $this->name = $name;

        return $this;
    }

    public function getCountry(): string
    {
        return $this->country;
    }

    public function setCountry(string $country): City
    {
        $this->country = $country;

        return $this;
    }

    /**
     * @return array{"lat":float,"lon":float}
     */
    public function getCoordinate(): array
    {
        return $this->coordinate;
    }

    /**
     * @param array{"lat":float,"lon":float} $coordinate
     *
     * @return $this
     */
    public function setCoordinate(array $coordinate): City
    {
        $this->coordinate = $coordinate;

        return $this;
    }

    public function getPopulation(): int
    {
        return $this->population;
    }

    public function setPopulation(int $population): City
    {
        $this->population = $population;

        return $this;
    }

    /**
     * @return array<string>
     */
    public function getLanguages(): array
    {
        return $this->languages;
    }

    /**
     * @param array<string> $languages
     *
     * @return $this
     */
    public function setLanguages(array $languages): City
    {
        $this->languages = $languages;

        return $this;
    }

    public function getAllLanguages(): string
    {
        return implode('|', $this->languages);
    }

    public function getGpsCoordinate(): string
    {
        return $this->coordinate['lat'].','.$this->coordinate['lon'];
    }
}
