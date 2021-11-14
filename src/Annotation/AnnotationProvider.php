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

use function array_key_exists;
use function count;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use function get_class;
use function is_string;
use MacFJA\RediSearch\Integration\AnnotationAttribute\DocumentId;
use MacFJA\RediSearch\Integration\AnnotationAttribute\GeoField;
use MacFJA\RediSearch\Integration\AnnotationAttribute\Index;
use MacFJA\RediSearch\Integration\AnnotationAttribute\NumericField;
use MacFJA\RediSearch\Integration\AnnotationAttribute\Suggestion;
use MacFJA\RediSearch\Integration\AnnotationAttribute\TagField;
use MacFJA\RediSearch\Integration\AnnotationAttribute\TextField;
use MacFJA\RediSearch\Integration\Mapping;
use MacFJA\RediSearch\Integration\MappingProvider;

class AnnotationProvider implements MappingProvider
{
    /** @var array<string,null|AnnotationMapping> */
    private $mappings = [];

    public function __construct()
    {
        if (!class_exists(AnnotationRegistry::class)) {
            return;
        }
        AnnotationRegistry::loadAnnotationClass(DocumentId::class);
        AnnotationRegistry::loadAnnotationClass(GeoField::class);
        AnnotationRegistry::loadAnnotationClass(Index::class);
        AnnotationRegistry::loadAnnotationClass(NumericField::class);
        AnnotationRegistry::loadAnnotationClass(Suggestion::class);
        AnnotationRegistry::loadAnnotationClass(TagField::class);
        AnnotationRegistry::loadAnnotationClass(TextField::class);
    }

    public function getMapping($instance): ?Mapping
    {
        if (!class_exists(AnnotationReader::class)) {
            return null;
        }
        $class = is_string($instance) ? $instance : get_class($instance);
        if (!array_key_exists($class, $this->mappings) && class_exists($class)) {
            $mapping = new AnnotationMapping($class);
            if (0 === count($mapping->getFields())) {
                $this->mappings[$class] = null;

                return null;
            }
            $this->mappings[$class] = $mapping;
        }

        return $this->mappings[$class] ?? null;
    }

    public function hasMappingFor(string $class): bool
    {
        return class_exists(AnnotationReader::class) && $this->getMapping($class) instanceof AnnotationMapping;
    }
}
