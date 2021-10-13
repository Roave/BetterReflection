<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection\StringCast;

use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\Reflection\Deprecated\DeprecatedHelper;

/**
 * @covers \Roave\BetterReflection\Reflection\Deprecated\DeprecatedHelper
 */
class DeprecatedHelperTest extends TestCase
{
    public function docCommentProvider(): array
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
     * @dataProvider docCommentProvider
     */
    public function testIsDeprecated(string $docComment, bool $isDeprecated): void
    {
        self::assertSame($isDeprecated, DeprecatedHelper::isDeprecated($docComment));
    }
}
