<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection\Exception;

use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\Reflection\Exception\NotAnObject;

/**
 * @covers \Roave\BetterReflection\Reflection\Exception\NotAnObject
 */
class NotAnObjectTest extends TestCase
{
    public function testFromNonObject(): void
    {
        $exception = NotAnObject::fromNonObject(123);

        self::assertInstanceOf(NotAnObject::class, $exception);
        self::assertSame('Provided "integer" is not an object', $exception->getMessage());
    }
}
