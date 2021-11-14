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

/**
 * @codeCoverageIgnore Value object
 */
class AddingDocumentToSearchEvent implements Event
{
    /** @var object */
    private $instance;

    /** @var array<string,bool|float|int|string> */
    private $data;

    /** @var null|string */
    private $documentId;

    /**
     * @param array<string,bool|float|int|string> $data
     */
    public function __construct(object $instance, array $data, ?string $documentId)
    {
        $this->instance = $instance;
        $this->data = $data;
        $this->documentId = $documentId;
    }

    public function getInstance(): object
    {
        return $this->instance;
    }

    /**
     * @return array<string,bool|float|int|string>
     */
    public function getData(): array
    {
        return $this->data;
    }

    public function getDocumentId(): ?string
    {
        return $this->documentId;
    }

    /**
     * @param array<string,bool|float|int|string> $data
     */
    public function setData(array $data): AddingDocumentToSearchEvent
    {
        $this->data = $data;

        return $this;
    }

    public function setDocumentId(?string $documentId): AddingDocumentToSearchEvent
    {
        $this->documentId = $documentId;

        return $this;
    }
}
