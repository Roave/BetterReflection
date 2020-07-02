<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection\Exception;

use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\Reflection\Exception\ObjectNotInstanceOfClass;

/**
 * @covers \Roave\BetterReflection\Reflection\Exception\ObjectNotInstanceOfClass
 */
class ObjectNotInstanceOfClassTest extends TestCase
{
    public function testFromClassName(): void
    {
        $exception = ObjectNotInstanceOfClass::fromClassName('Foo');

        self::assertInstanceOf(ObjectNotInstanceOfClass::class, $exception);
        self::assertSame('Object is not instance of class "Foo"', $exception->getMessage());
    }
}
