<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\Reflection\Exception\CircularReference;

#[CoversClass(CircularReference::class)]
class CircularReferenceTest extends TestCase
{
    public function testFromNonObject(): void
    {
        $exception = CircularReference::fromClassName('Whatever');

        self::assertInstanceOf(CircularReference::class, $exception);
        self::assertSame('Circular reference to class "Whatever"', $exception->getMessage());
    }
}
