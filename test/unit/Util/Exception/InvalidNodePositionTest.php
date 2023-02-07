<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Util\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\Util\Exception\InvalidNodePosition;

#[CoversClass(InvalidNodePosition::class)]
class InvalidNodePositionTest extends TestCase
{
    public function testFromPosition(): void
    {
        $exception = InvalidNodePosition::fromPosition(123);

        self::assertInstanceOf(InvalidNodePosition::class, $exception);
        self::assertSame('Invalid position 123', $exception->getMessage());
    }
}
