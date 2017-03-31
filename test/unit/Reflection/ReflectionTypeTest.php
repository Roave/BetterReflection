<?php

namespace Roave\BetterReflectionTest\Reflection;

use Roave\BetterReflection\Reflection\ReflectionType;
use phpDocumentor\Reflection\Fqsen;
use phpDocumentor\Reflection\Types;

/**
 * @covers \Roave\BetterReflection\Reflection\ReflectionType
 */
class ReflectionTypeTest extends \PHPUnit_Framework_TestCase
{
    public function testCreateFromType()
    {
        $type = new Types\String_();
        $typeInfo = ReflectionType::createFromType($type, false);
        self::assertInstanceOf(ReflectionType::class, $typeInfo);
    }

    public function testGetTypeObject()
    {
        $type = new Types\String_();
        $typeInfo = ReflectionType::createFromType($type, false);
        self::assertSame($type, $typeInfo->getTypeObject());
    }

    public function testAllowsNull()
    {
        $noNullType = ReflectionType::createFromType(new Types\String_(), false);
        self::assertFalse($noNullType->allowsNull());

        $allowsNullType = ReflectionType::createFromType(new Types\String_(), true);
        self::assertTrue($allowsNullType->allowsNull());
    }

    public function testIsBuiltin()
    {
        self::assertTrue(ReflectionType::createFromType(new Types\String_(), false)->isBuiltin());
        self::assertTrue(ReflectionType::createFromType(new Types\Integer(), false)->isBuiltin());
        self::assertTrue(ReflectionType::createFromType(new Types\Array_(), false)->isBuiltin());
        self::assertFalse(ReflectionType::createFromType(new Types\Object_(), false)->isBuiltin());
    }

    public function testImplicitCastToString()
    {
        self::assertSame('int', (string)ReflectionType::createFromType(new Types\Integer(), false));
        self::assertSame('string', (string)ReflectionType::createFromType(new Types\String_(), false));
        self::assertSame('array', (string)ReflectionType::createFromType(new Types\Array_(), false));
        self::assertSame('callable', (string)ReflectionType::createFromType(new Types\Callable_(), false));
        self::assertSame('bool', (string)ReflectionType::createFromType(new Types\Boolean(), false));
        self::assertSame('float', (string)ReflectionType::createFromType(new Types\Float_(), false));
        self::assertSame('void', (string)ReflectionType::createFromType(new Types\Void_(), false));

        self::assertSame('Foo\Bar\Baz', (string)ReflectionType::createFromType(
            new Types\Object_(new Fqsen('\Foo\Bar\Baz')),
            false
        ));
    }
}
