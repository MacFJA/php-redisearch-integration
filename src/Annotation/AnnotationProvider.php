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
use function assert;
use function class_exists;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use MacFJA\RediSearch\Integration\Helper\CommonDynamicMapperMethods;
use MacFJA\RediSearch\Integration\MappedClass;
use MacFJA\RediSearch\Integration\MappedClassProvider;

class AnnotationProvider implements MappedClassProvider
{
    use CommonDynamicMapperMethods;

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

    /**
     * @param string|class-string $classname
     */
    protected function getMapped(string $classname): ?MappedClass
    {
        if (!class_exists(AnnotationReader::class)) {
            return null;
        }
        if (!class_exists($classname)) {
            return null;
        }
        if (!array_key_exists($classname, $this->mapped)) {
            $anonymous = new class() extends TemplateAnnotationMapper {
            };

            $anonymous::init($classname);

            $this->mapped[$classname] = $anonymous;
            if (!$anonymous::isValid()) {
                $this->mapped[$classname] = null;
            }
        }

        $mapped = $this->mapped[$classname];
        assert($mapped instanceof TemplateAnnotationMapper || null === $mapped);

        return $mapped;
    }
}
