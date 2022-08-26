<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\SourceLocator\Exception;

use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\SourceLocator\Exception\NoAnonymousClassOnLine;

/** @covers \Roave\BetterReflection\SourceLocator\Exception\NoAnonymousClassOnLine */
class NoAnonymousClassOnLineTest extends TestCase
{
    public function testCreate(): void
    {
        $exception = NoAnonymousClassOnLine::create('foo.php', 123);

        self::assertInstanceOf(NoAnonymousClassOnLine::class, $exception);
        self::assertSame('No anonymous class found on line 123 in foo.php', $exception->getMessage());
    }
}
