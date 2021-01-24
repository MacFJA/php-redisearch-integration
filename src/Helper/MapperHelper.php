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

namespace MacFJA\RediSearch\Integration\Helper;

use function array_map;
use function array_merge;
use function count;
use function implode;
use function is_array;
use function is_string;
use function preg_split;
use DateTimeInterface;
use MacFJA\RediSearch\Helper\DataHelper;
use MacFJA\RediSearch\Index\Builder\Field;
use MacFJA\RediSearch\Index\Builder\TagField;
use Throwable;

trait MapperHelper
{
    /**
     * @param null|mixed $data
     *
     * @throws Throwable
     *
     * @return null|mixed
     */
    protected function serializedData($data, Field $field)
    {
        if ($field instanceof TagField && is_array($data)) {
            DataHelper::assertArrayOf($data, 'scalar');

            return implode($field->getSeparator() ?? ', ', $data);
        }
        if (Field::TYPE_TEXT === $field->getType() && $data instanceof DateTimeInterface) {
            return $data->format(\DATE_W3C);
        }

        return $data;
    }

    /**
     * @param array<float|int|string> $data
     *
     * @throws Throwable
     */
    protected function flatten(array $data, string $separator = ' '): string
    {
        DataHelper::assertArrayOf($data, 'scalar');

        return implode($separator, $data);
    }

    /**
     * @return array<string>
     */
    protected function splitString(string $value): array
    {
        return preg_split('/(?:[[:punct:]]|\s)+/', $value) ?: [];
    }

    /**
     * @param array<string>|string $source
     *
     * @return array<array{value:string,score:float,payload:string|null,increment:bool}>
     */
    protected function suggestionValues($source, bool $wordsMode, float $score = 1.0, ?string $payload = null, bool $increment = false): array
    {
        if (is_array($source)) {
            $values = [];
            foreach ($source as $item) {
                $values = array_merge($values, $this->suggestionValues($item, $wordsMode, $score, $payload, $increment));
            }

            return $values;
        }
        if (!is_string($source)) {
            return [];
        }
        $values = [[
            'value' => $source,
            'score' => $score,
            'payload' => $payload,
            'increment' => $increment,
        ]];
        if (true === $wordsMode) {
            $words = $this->splitString($source);
            if (1 === count($words)) {
                return $values;
            }
            /** @var array<array{value:string,score:float,payload:string|null,increment:bool}> $words */
            $words = array_map(
                function (string $word) use ($score, $payload, $increment): array {
                    return [
                        'value' => $word,
                        'score' => $score,
                        'payload' => $payload,
                        'increment' => $increment,
                    ];
                },
                $words
            );
            $values = array_merge($values, $words);
        }

        return $values;
    }
}
