<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection\Exception;

use PhpParser\Node;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\Reflection\Exception\InvalidConstantNode;

/**
 * @covers \Roave\BetterReflection\Reflection\Exception\InvalidConstantNode
 */
class InvalidConstantNodeTest extends TestCase
{
    public function testCreate(): void
    {
        $exception = InvalidConstantNode::create(new Node\Name('Whatever'));

        self::assertInstanceOf(InvalidConstantNode::class, $exception);
        self::assertSame('Invalid constant node (first 50 characters: Whatever)', $exception->getMessage());
    }
}
