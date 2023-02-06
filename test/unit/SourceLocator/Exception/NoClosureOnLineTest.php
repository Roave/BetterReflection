<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\SourceLocator\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\SourceLocator\Exception\NoClosureOnLine;

#[CoversClass(NoClosureOnLine::class)]
class NoClosureOnLineTest extends TestCase
{
    public function testCreate(): void
    {
        $exception = NoClosureOnLine::create('foo.php', 123);

        self::assertInstanceOf(NoClosureOnLine::class, $exception);
        self::assertSame('No closure found on line 123 in foo.php', $exception->getMessage());
    }
}
