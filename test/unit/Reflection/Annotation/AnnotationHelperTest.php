<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection\Annotation;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\Reflection\Annotation\AnnotationHelper;

#[CoversClass(AnnotationHelper::class)]
class AnnotationHelperTest extends TestCase
{
    /** @return list<array{0: string|null, 1: bool}> */
    public static function deprecatedDocCommentProvider(): array
    {
        return [
            [null, false],
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

    #[DataProvider('deprecatedDocCommentProvider')]
    public function testIsDeprecated(string|null $docComment, bool $isDeprecated): void
    {
        self::assertSame($isDeprecated, AnnotationHelper::isDeprecated($docComment));
    }

    /** @return list<array{0: string|null, 1: bool}> */
    public static function tentativeReturnTypeDocCommentProvider(): array
    {
        return [
            [null, false],
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

    #[DataProvider('tentativeReturnTypeDocCommentProvider')]
    public function testhasTentativeReturnType(string|null $docComment, bool $isDeprecated): void
    {
        self::assertSame($isDeprecated, AnnotationHelper::hasTentativeReturnType($docComment));
    }
}
