<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\SourceLocator\Type\Composer\Psr\Exception;

use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\SourceLocator\Type\Composer\Psr\Exception\InvalidPrefixMapping;

/**
 * @covers \Roave\BetterReflection\SourceLocator\Type\Composer\Psr\Exception\InvalidPrefixMapping
 */
final class InvalidPrefixMappingTest extends TestCase
{
    public function testEmptyPrefixGiven(): void
    {
        self::assertSame(
            'An invalid empty string provided as a PSR mapping prefix',
            InvalidPrefixMapping::emptyPrefixGiven()
                ->getMessage(),
        );
    }

    public function testPrefixMappingIsNotADirectory(): void
    {
        self::assertSame(
            'Provided path "A\" for prefix "a/b" is not a directory',
            InvalidPrefixMapping::prefixMappingIsNotADirectory('A\\', 'a/b')
                ->getMessage(),
        );
    }

    public function testEmptyPrefixMappingGiven(): void
    {
        self::assertSame(
            'An invalid empty list of paths was provided for PSR mapping prefix "A\"',
            InvalidPrefixMapping::emptyPrefixMappingGiven('A\\')
                ->getMessage(),
        );
    }
}
