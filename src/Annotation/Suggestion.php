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

namespace MacFJA\RediSearch\Integration\Annotation;

use Doctrine\Common\Annotations\NamedArgumentConstructorAnnotation;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD"})
 */
class Suggestion implements NamedArgumentConstructorAnnotation
{
    public const TYPE_FULL = 'full';

    public const TYPE_WORD = 'word';

    /** @var string */
    private $group;

    /** @var string */
    private $type;

    /** @var null|string */
    private $payload;

    /** @var float */
    private $score;

    /** @var bool */
    private $increment;

    /**
     * Suggestion constructor.
     */
    public function __construct(string $group = 'suggestion', float $score = 1.0, string $type = self::TYPE_FULL, bool $increment = false, ?string $payload = null)
    {
        $this->group = $group;
        $this->type = $type;
        $this->score = $score;
        $this->payload = $payload;
        $this->increment = $increment;
    }

    public function getGroup(): string
    {
        return $this->group;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getPayload(): ?string
    {
        return $this->payload;
    }

    public function getScore(): float
    {
        return $this->score;
    }

    public function isIncrement(): bool
    {
        return $this->increment;
    }
}
