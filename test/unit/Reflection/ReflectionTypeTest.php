<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection;

use Generator;
use PhpParser\Node\Identifier;
use PhpParser\Node\NullableType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\Reflection\ReflectionNamedType;
use Roave\BetterReflection\Reflection\ReflectionType;
use Roave\BetterReflection\Reflector\Reflector;

/**
 * @covers \Roave\BetterReflection\Reflection\ReflectionType
 */
class ReflectionTypeTest extends TestCase
{
    private Reflector|MockObject $reflector;

    protected function setUp(): void
    {
        parent::setUp();

        $this->reflector = $this->createMock(Reflector::class);
    }

    public function testCreateFromType(): void
    {
        $typeInfo = ReflectionType::createFromTypeAndReflector(new Identifier('string'));
        self::assertInstanceOf(ReflectionType::class, $typeInfo);
    }

    public function testAllowsNull(): void
    {
        $noNullType = ReflectionType::createFromTypeAndReflector(new Identifier('string'));
        self::assertFalse($noNullType->allowsNull());

        $allowsNullType = ReflectionType::createFromTypeAndReflector(new NullableType(new Identifier('string')));
        self::assertTrue($allowsNullType->allowsNull());
    }

    public function isBuildinProvider(): Generator
    {
        yield ['string'];
        yield ['int'];
        yield ['array'];
        yield ['object'];
        yield ['iterable'];
        yield ['mixed'];
    }

    /**
     * @dataProvider isBuildinProvider
     */
    public function testIsBuiltin(string $type): void
    {
        $reflectionType = ReflectionType::createFromTypeAndReflector(new Identifier($type));

        self::assertInstanceOf(ReflectionNamedType::class, $reflectionType);
        self::assertTrue($reflectionType->isBuiltin());
    }

    public function isNotBuildinProvider(): Generator
    {
        yield ['foo'];
        yield ['\foo'];
    }

    /**
     * @dataProvider isNotBuildinProvider
     */
    public function testIsNotBuiltin(string $type): void
    {
        $reflectionType = ReflectionType::createFromTypeAndReflector(new Identifier($type));

        self::assertInstanceOf(ReflectionNamedType::class, $reflectionType);
        self::assertFalse($reflectionType->isBuiltin());
    }

    public function testImplicitCastToString(): void
    {
        self::assertSame('int', (string) ReflectionType::createFromTypeAndReflector(new Identifier('int')));
        self::assertSame('string', (string) ReflectionType::createFromTypeAndReflector(new Identifier('string')));
        self::assertSame('array', (string) ReflectionType::createFromTypeAndReflector(new Identifier('array')));
        self::assertSame('callable', (string) ReflectionType::createFromTypeAndReflector(new Identifier('callable')));
        self::assertSame('bool', (string) ReflectionType::createFromTypeAndReflector(new Identifier('bool')));
        self::assertSame('float', (string) ReflectionType::createFromTypeAndReflector(new Identifier('float')));
        self::assertSame('void', (string) ReflectionType::createFromTypeAndReflector(new Identifier('void')));
        self::assertSame('object', (string) ReflectionType::createFromTypeAndReflector(new Identifier('object')));
        self::assertSame('iterable', (string) ReflectionType::createFromTypeAndReflector(new Identifier('iterable')));
        self::assertSame('mixed', (string) ReflectionType::createFromTypeAndReflector(new Identifier('mixed')));

        self::assertSame('Foo\Bar\Baz', (string) ReflectionType::createFromTypeAndReflector(new Identifier('Foo\Bar\Baz')));
        self::assertSame('\Foo\Bar\Baz', (string) ReflectionType::createFromTypeAndReflector(new Identifier('\Foo\Bar\Baz')));
    }
}
