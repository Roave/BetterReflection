<?php
declare(strict_types=1);

namespace Rector\BetterReflectionTest\Reflection;

use PHPUnit\Framework\TestCase;
use Rector\BetterReflection\Reflection\ReflectionType;

/**
 * @covers \Rector\BetterReflection\Reflection\ReflectionType
 */
class ReflectionTypeTest extends TestCase
{
    public function testCreateFromType() : void
    {
        $typeInfo = ReflectionType::createFromType('string', false);
        self::assertInstanceOf(ReflectionType::class, $typeInfo);
    }

    public function testAllowsNull() : void
    {
        $noNullType = ReflectionType::createFromType('string', false);
        self::assertFalse($noNullType->allowsNull());

        $allowsNullType = ReflectionType::createFromType('string', true);
        self::assertTrue($allowsNullType->allowsNull());
    }

    public function testIsBuiltin() : void
    {
        self::assertTrue(ReflectionType::createFromType('string', false)->isBuiltin());
        self::assertTrue(ReflectionType::createFromType('int', false)->isBuiltin());
        self::assertTrue(ReflectionType::createFromType('array', false)->isBuiltin());
        self::assertTrue(ReflectionType::createFromType('object', false)->isBuiltin());
        self::assertTrue(ReflectionType::createFromType('iterable', false)->isBuiltin());
        self::assertFalse(ReflectionType::createFromType('foo', false)->isBuiltin());
        self::assertFalse(ReflectionType::createFromType('\foo', false)->isBuiltin());
    }

    public function testImplicitCastToString() : void
    {
        self::assertSame('int', (string) ReflectionType::createFromType('int', false));
        self::assertSame('string', (string) ReflectionType::createFromType('string', false));
        self::assertSame('array', (string) ReflectionType::createFromType('array', false));
        self::assertSame('callable', (string) ReflectionType::createFromType('callable', false));
        self::assertSame('bool', (string) ReflectionType::createFromType('bool', false));
        self::assertSame('float', (string) ReflectionType::createFromType('float', false));
        self::assertSame('void', (string) ReflectionType::createFromType('void', false));
        self::assertSame('object', (string) ReflectionType::createFromType('object', false));
        self::assertSame('iterable', (string) ReflectionType::createFromType('iterable', false));

        self::assertSame('Foo\Bar\Baz', (string) ReflectionType::createFromType('Foo\Bar\Baz', false));
        self::assertSame('Foo\Bar\Baz', (string) ReflectionType::createFromType('\Foo\Bar\Baz', false));
    }
}
