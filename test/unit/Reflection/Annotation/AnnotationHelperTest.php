<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection\Annotation;

use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\Reflection\Annotation\AnnotationHelper;

/**
 * @covers \Roave\BetterReflection\Reflection\Annotation\AnnotationHelper
 */
class AnnotationHelperTest extends TestCase
{
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
            [
                '/**
                 * @deprecated since 8.0.0
                 */',
                true,
            ],
        ];
    }

    /**
     * @dataProvider deprecatedDocCommentProvider
     */
    public function testIsDeprecated(string $docComment, bool $isDeprecated): void
    {
        self::assertSame($isDeprecated, AnnotationHelper::isDeprecated($docComment));
    }
}
