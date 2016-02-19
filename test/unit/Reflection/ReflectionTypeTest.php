<?php

namespace BetterReflectionTest\Reflection;

use BetterReflection\Reflection\ReflectionType;
use phpDocumentor\Reflection\Fqsen;
use phpDocumentor\Reflection\Types;

/**
 * @covers \BetterReflection\Reflection\ReflectionType
 */
class ReflectionTypeTest extends \PHPUnit_Framework_TestCase
{
    public function testCreateFromType()
    {
        $type = new Types\String_();
        $typeInfo = ReflectionType::createFromType($type, false);
        $this->assertInstanceOf(ReflectionType::class, $typeInfo);
    }

    public function testGetTypeObject()
    {
        $type = new Types\String_();
        $typeInfo = ReflectionType::createFromType($type, false);
        $this->assertSame($type, $typeInfo->getTypeObject());
    }

    public function testAllowsNull()
    {
        $noNullType = ReflectionType::createFromType(new Types\String_(), false);
        $this->assertFalse($noNullType->allowsNull());

        $allowsNullType = ReflectionType::createFromType(new Types\String_(), true);
        $this->assertTrue($allowsNullType->allowsNull());
    }

    public function testIsBuiltin()
    {
        $this->assertTrue(ReflectionType::createFromType(new Types\String_(), false)->isBuiltin());
        $this->assertTrue(ReflectionType::createFromType(new Types\Integer(), false)->isBuiltin());
        $this->assertTrue(ReflectionType::createFromType(new Types\Array_(), false)->isBuiltin());
        $this->assertFalse(ReflectionType::createFromType(new Types\Object_(), false)->isBuiltin());
    }

    public function testImplicitCastToString()
    {
        $this->assertSame('int', (string)ReflectionType::createFromType(new Types\Integer(), false));
        $this->assertSame('string', (string)ReflectionType::createFromType(new Types\String_(), false));
        $this->assertSame('array', (string)ReflectionType::createFromType(new Types\Array_(), false));
        $this->assertSame('callable', (string)ReflectionType::createFromType(new Types\Callable_(), false));
        $this->assertSame('bool', (string)ReflectionType::createFromType(new Types\Boolean(), false));
        $this->assertSame('float', (string)ReflectionType::createFromType(new Types\Float_(), false));
        $this->assertSame('void', (string)ReflectionType::createFromType(new Types\Void(), false));

        $this->assertSame('\Foo\Bar\Baz', (string)ReflectionType::createFromType(
            new Types\Object_(new Fqsen('\Foo\Bar\Baz')),
            false
        ));
    }
}
