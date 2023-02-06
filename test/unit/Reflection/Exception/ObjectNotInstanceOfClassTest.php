<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\Reflection\Exception\ObjectNotInstanceOfClass;

#[CoversClass(ObjectNotInstanceOfClass::class)]
class ObjectNotInstanceOfClassTest extends TestCase
{
    public function testFromClassName(): void
    {
        $exception = ObjectNotInstanceOfClass::fromClassName('Foo');

        self::assertInstanceOf(ObjectNotInstanceOfClass::class, $exception);
        self::assertSame('Object is not instance of class "Foo"', $exception->getMessage());
    }
}
