<?php
declare(strict_types=1);

namespace Rector\BetterReflectionTest\Util\Exception;

use PHPUnit\Framework\TestCase;
use Rector\BetterReflection\Util\Exception\InvalidNodePosition;

/**
 * @covers \Rector\BetterReflection\Util\Exception\InvalidNodePosition
 */
class InvalidNodePositionTest extends TestCase
{
    public function testFromPosition() : void
    {
        $exception = InvalidNodePosition::fromPosition(123);

        self::assertInstanceOf(InvalidNodePosition::class, $exception);
        self::assertSame('Invalid position 123', $exception->getMessage());
    }
}
