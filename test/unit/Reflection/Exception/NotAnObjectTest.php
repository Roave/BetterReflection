<?php

namespace BetterReflectionTest\Reflection\Exception;

use BetterReflection\Reflection\Exception\NotAnObject;
use BetterReflectionTest\ClassWithInterfaces;
use BetterReflectionTest\ClassWithInterfacesOther;
use PHPUnit_Framework_TestCase;

/**
 * @covers \BetterReflection\Reflection\Exception\NotAnObject
 */
class NotAnObjectTest extends PHPUnit_Framework_TestCase
{
    public function testFromNonObject()
    {
        $exception = NotAnObject::fromNonObject(123);

        $this->assertInstanceOf(NotAnObject::class, $exception);
        $this->assertSame('Provided "integer" is not an object', $exception->getMessage());
    }
}
