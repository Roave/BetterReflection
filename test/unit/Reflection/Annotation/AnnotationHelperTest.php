<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection\Annotation;

use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\Reflection\Annotation\AnnotationHelper;

/** @covers \Roave\BetterReflection\Reflection\Annotation\AnnotationHelper */
class AnnotationHelperTest extends TestCase
{
    /** @return list<array{0: string, 1: bool}> */
    public function deprecatedDocCommentProvider(): array
    {
        return [
            ['', false],
            [
                '/**
                 * @return string
                 */',
                false,
            ],
            [
                '/**
                 * @deprecatedPolicy
                 */',
                false,
            ],
            ['/** @deprecated */', true],
            ['/**@deprecated*/', true],
            [
                '/**
                 * @deprecated since 8.0.0
                 */',
                true,
            ],
        ];
    }

    /** @dataProvider deprecatedDocCommentProvider */
    public function testIsDeprecated(string $docComment, bool $isDeprecated): void
    {
        self::assertSame($isDeprecated, AnnotationHelper::isDeprecated($docComment));
    }

    /** @return list<array{0: string, 1: bool}> */
    public function tentativeReturnTypeDocCommentProvider(): array
    {
        return [
            ['', false],
            [
                '/**
                 * @return string
                 */',
                false,
            ],
            ['/** @betterReflectionTentativeReturnType */', true],
            ['/**@betterReflectionTentativeReturnType*/', true],
            [
                '/**
                 * @betterReflectionTentativeReturnType
                 */',
                true,
            ],
        ];
    }

    /** @dataProvider tentativeReturnTypeDocCommentProvider */
    public function testhasTentativeReturnType(string $docComment, bool $isDeprecated): void
    {
        self::assertSame($isDeprecated, AnnotationHelper::hasTentativeReturnType($docComment));
    }
}
