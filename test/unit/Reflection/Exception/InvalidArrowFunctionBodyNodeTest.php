<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection\Exception;

use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Echo_;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\Reflection\Exception\InvalidArrowFunctionBodyNode;

#[CoversClass(InvalidArrowFunctionBodyNode::class)]
class InvalidArrowFunctionBodyNodeTest extends TestCase
{
    public function testCreate(): void
    {
        $exception = InvalidArrowFunctionBodyNode::create(new Echo_([
            new String_('Hello world with very long string so it is truncated!'),
        ]));

        self::assertInstanceOf(InvalidArrowFunctionBodyNode::class, $exception);
        self::assertSame("Invalid arrow function body node (first 50 characters: echo 'Hello world with very long string so it is t)", $exception->getMessage());
    }
}
