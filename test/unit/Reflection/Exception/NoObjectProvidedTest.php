<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\Reflection\Exception\NoObjectProvided;

#[CoversClass(NoObjectProvided::class)]
class NoObjectProvidedTest extends TestCase
{
    public function testFromClassName(): void
    {
        $exception = NoObjectProvided::create();

        self::assertInstanceOf(NoObjectProvided::class, $exception);
        self::assertSame('No object provided', $exception->getMessage());
    }
}
