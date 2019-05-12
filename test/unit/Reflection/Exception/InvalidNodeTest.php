<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection\Exception;

use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\Reflection\Exception\InvalidNode;

/**
 * @covers \Roave\BetterReflection\Reflection\Exception\InvalidNode
 */
class InvalidNodeTest extends TestCase
{
    public function testCreate() : void
    {
        $exception = InvalidNode::create();

        self::assertInstanceOf(InvalidNode::class, $exception);
        self::assertSame('Invalid node', $exception->getMessage());
    }
}
