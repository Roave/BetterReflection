<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection\Deprecated;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\Reflection\Deprecated\DeprecatedHelper;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflector\DefaultReflector;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;
use Roave\BetterReflectionTest\BetterReflectionSingleton;

use function sprintf;

#[CoversClass(DeprecatedHelper::class)]
class DeprecatedHelperTest extends TestCase
{
    /** @return list<array{0: string, 1: bool}> */
    public static function deprecatedAttributeProvider(): array
    {
        return [
            [
                '',
                false,
            ],
            [
                '#[SomeAttribute]',
                false,
            ],
            [
                '#[Deprecated]',
                true,
            ],
            [
                '#[Deprecated(since: "8.0.0")]',
                true,
            ],
            [
                '#[SomeAttribute] #[Deprecated]',
                true,
            ],
        ];
    }

    #[DataProvider('deprecatedAttributeProvider')]
    public function testIsDeprecatedByAttribute(string $deprecatedCode, bool $isDeprecated): void
    {
        $php = sprintf('<?php
        %s
        function foo() {}', $deprecatedCode);

        $reflector  = new DefaultReflector(new StringSourceLocator($php, BetterReflectionSingleton::instance()->astLocator()));
        $reflection = $reflector->reflectFunction('foo');

        self::assertSame($isDeprecated, DeprecatedHelper::isDeprecated($reflection));
    }

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
    public function testIsDeprecatedByDocComment(string|null $docComment, bool $isDeprecated): void
    {
        $reflection = self::createStub(ReflectionClass::class);
        $reflection
            ->method('getDocComment')
            ->willReturn($docComment);

        self::assertSame($isDeprecated, DeprecatedHelper::isDeprecated($reflection));
    }
}
