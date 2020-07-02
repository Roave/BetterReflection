<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\Reflection\Exception\ClassDoesNotExist;
use Roave\BetterReflection\Reflection\Exception\ReflectionTypeDoesNotPointToAClassAlikeType;
use Roave\BetterReflection\Reflection\Reflection;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionType;
use Roave\BetterReflection\Reflector\Reflector;

/**
 * @covers \Roave\BetterReflection\Reflection\ReflectionType
 */
class ReflectionTypeTest extends TestCase
{
    /** @var Reflector|MockObject */
    private $reflector;

    protected function setUp(): void
    {
        parent::setUp();

        $this->reflector = $this->createMock(Reflector::class);
    }

    public function testCreateFromType(): void
    {
        $typeInfo = ReflectionType::createFromTypeAndReflector('string', false, $this->reflector);
        self::assertInstanceOf(ReflectionType::class, $typeInfo);
    }

    public function testAllowsNull(): void
    {
        $noNullType = ReflectionType::createFromTypeAndReflector('string', false, $this->reflector);
        self::assertFalse($noNullType->allowsNull());

        $allowsNullType = ReflectionType::createFromTypeAndReflector('string', true, $this->reflector);
        self::assertTrue($allowsNullType->allowsNull());
    }

    public function testIsBuiltin(): void
    {
        self::assertTrue(ReflectionType::createFromTypeAndReflector('string', false, $this->reflector)->isBuiltin());
        self::assertTrue(ReflectionType::createFromTypeAndReflector('int', false, $this->reflector)->isBuiltin());
        self::assertTrue(ReflectionType::createFromTypeAndReflector('array', false, $this->reflector)->isBuiltin());
        self::assertTrue(ReflectionType::createFromTypeAndReflector('object', false, $this->reflector)->isBuiltin());
        self::assertTrue(ReflectionType::createFromTypeAndReflector('iterable', false, $this->reflector)->isBuiltin());
        self::assertFalse(ReflectionType::createFromTypeAndReflector('foo', false, $this->reflector)->isBuiltin());
        self::assertFalse(ReflectionType::createFromTypeAndReflector('\foo', false, $this->reflector)->isBuiltin());
    }

    public function testGetName(): void
    {
        self::assertSame('int', ReflectionType::createFromTypeAndReflector('int', false, $this->reflector)->getName());
        self::assertSame('string', ReflectionType::createFromTypeAndReflector('string', false, $this->reflector)->getName());
        self::assertSame('array', ReflectionType::createFromTypeAndReflector('array', false, $this->reflector)->getName());
        self::assertSame('callable', ReflectionType::createFromTypeAndReflector('callable', false, $this->reflector)->getName());
        self::assertSame('bool', ReflectionType::createFromTypeAndReflector('bool', false, $this->reflector)->getName());
        self::assertSame('float', ReflectionType::createFromTypeAndReflector('float', false, $this->reflector)->getName());
        self::assertSame('void', ReflectionType::createFromTypeAndReflector('void', false, $this->reflector)->getName());
        self::assertSame('object', ReflectionType::createFromTypeAndReflector('object', false, $this->reflector)->getName());
        self::assertSame('iterable', ReflectionType::createFromTypeAndReflector('iterable', false, $this->reflector)->getName());

        self::assertSame('Foo\Bar\Baz', ReflectionType::createFromTypeAndReflector('Foo\Bar\Baz', false, $this->reflector)->getName());
        self::assertSame('Foo\Bar\Baz', ReflectionType::createFromTypeAndReflector('\Foo\Bar\Baz', false, $this->reflector)->getName());
    }

    public function testImplicitCastToString(): void
    {
        self::assertSame('int', (string) ReflectionType::createFromTypeAndReflector('int', false, $this->reflector));
        self::assertSame('string', (string) ReflectionType::createFromTypeAndReflector('string', false, $this->reflector));
        self::assertSame('array', (string) ReflectionType::createFromTypeAndReflector('array', false, $this->reflector));
        self::assertSame('callable', (string) ReflectionType::createFromTypeAndReflector('callable', false, $this->reflector));
        self::assertSame('bool', (string) ReflectionType::createFromTypeAndReflector('bool', false, $this->reflector));
        self::assertSame('float', (string) ReflectionType::createFromTypeAndReflector('float', false, $this->reflector));
        self::assertSame('void', (string) ReflectionType::createFromTypeAndReflector('void', false, $this->reflector));
        self::assertSame('object', (string) ReflectionType::createFromTypeAndReflector('object', false, $this->reflector));
        self::assertSame('iterable', (string) ReflectionType::createFromTypeAndReflector('iterable', false, $this->reflector));

        self::assertSame('Foo\Bar\Baz', (string) ReflectionType::createFromTypeAndReflector('Foo\Bar\Baz', false, $this->reflector));
        self::assertSame('Foo\Bar\Baz', (string) ReflectionType::createFromTypeAndReflector('\Foo\Bar\Baz', false, $this->reflector));
    }

    public function testWillDisallowFetchingTargetClassForInternalTypes(): void
    {
        $type = ReflectionType::createFromTypeAndReflector(
            'int',
            true,
            $this->reflector,
        );

        $this
            ->reflector
            ->expects(self::never())
            ->method('reflect');

        $this->expectException(ReflectionTypeDoesNotPointToAClassAlikeType::class);

        $type->targetReflectionClass();
    }

    public function testWillDisallowRetrievingIncompatibleReflectionTypesForClassTypes(): void
    {
        $type = ReflectionType::createFromTypeAndReflector(
            '\Foo\Bar',
            true,
            $this->reflector,
        );

        $reflection = $this->createMock(Reflection::class);

        $this
            ->reflector
            ->expects(self::once())
            ->method('reflect')
            ->with('Foo\Bar')
            ->willReturn($reflection);

        $this->expectException(ClassDoesNotExist::class);

        $type->targetReflectionClass();
    }

    public function testWillRetrieveTargetClass(): void
    {
        $type = ReflectionType::createFromTypeAndReflector(
            'Foo\Bar',
            true,
            $this->reflector,
        );

        $reflection = $this->createMock(ReflectionClass::class);

        $this
            ->reflector
            ->expects(self::any())
            ->method('reflect')
            ->with('Foo\Bar')
            ->willReturn($reflection);

        self::assertSame($reflection, $type->targetReflectionClass());
    }
}
