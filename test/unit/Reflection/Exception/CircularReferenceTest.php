<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection\Exception;

use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\Reflection\Exception\CircularReference;

/** @covers \Roave\BetterReflection\Reflection\Exception\CircularReference */
class CircularReferenceTest extends TestCase
{
    public function testFromNonObject(): void
    {
        $exception = CircularReference::fromClassName('Whatever');

        self::assertInstanceOf(CircularReference::class, $exception);
        self::assertSame('Circular reference to class "Whatever"', $exception->getMessage());
    }
}
