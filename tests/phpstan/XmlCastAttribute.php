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

namespace MacFJA\RediSearch\Integration\tests\phpstan;

use function count;
use InvalidArgumentException;
use MacFJA\RediSearch\Integration\Xml\TemplateXmlMapper;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\PrettyPrinter\Standard;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\BooleanType;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;
use PHPStan\Type\FloatType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\NullType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;

class XmlCastAttribute implements DynamicStaticMethodReturnTypeExtension
{
    public function getClass(): string
    {
        return TemplateXmlMapper::class;
    }

    public function isStaticMethodSupported(MethodReflection $methodReflection): bool
    {
        return 'castAttributeOrNull' === $methodReflection->getName();
    }

    public function getTypeFromStaticMethodCall(MethodReflection $methodReflection, StaticCall $methodCall, Scope $scope): Type
    {
        $printer = new Standard();
        if (count($methodCall->args) < 3) {
            $cast = '\'string\'';
        } else {
            $cast = $printer->prettyPrint([$methodCall->args[2]]);
        }

        switch ($cast) {
            case '\'string\'':
                $castType = new StringType();

                break;
            case '\'integer\'':
                $castType = new IntegerType();

                break;
            case '\'boolean\'':
                $castType = new BooleanType();

                break;
            case '\'double\'':
                $castType = new FloatType();

                break;
            default:
                throw new InvalidArgumentException('Cast to type '.$cast.' not supported');
        }

        return new UnionType([new NullType(), $castType]);
    }
}
