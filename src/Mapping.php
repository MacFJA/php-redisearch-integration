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

use MacFJA\RediSearch\Integration\Mapping\SuggestionMapping;
use MacFJA\RediSearch\Redis\Command\CreateCommand\CreateCommandFieldOption;

interface Mapping
{
    public function getIndexName(): string;

    public function getDocumentPrefix(): ?string;

    /**
     * @return null|array<string>
     */
    public function getStopWords(): ?array;

    /**
     * @return array<string,CreateCommandFieldOption>
     */
    public function getFields(): array;

    public function getDocumentId(object $object): ?string;

    /**
     * @return array<string,bool|float|int|string>
     */
    public function getData(object $object): array;

    /**
     * @return array<SuggestionMapping>
     */
    public function getSuggestion(object $object): array;

    /**
     * @return array<string>
     */
    public function getSuggestionGroups(): array;
}
