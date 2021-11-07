<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection;

use Generator;
use PhpParser\Node\Identifier;
use PhpParser\Node\NullableType;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\Reflection\ReflectionNamedType;
use Roave\BetterReflection\Reflection\ReflectionParameter;
use Roave\BetterReflection\Reflection\ReflectionType;
use Roave\BetterReflection\Reflector\Reflector;

/**
 * @covers \Roave\BetterReflection\Reflection\ReflectionType
 */
class ReflectionTypeTest extends TestCase
{
    private Reflector $reflector;
    private ReflectionParameter $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->reflector = $this->createMock(Reflector::class);
        $this->owner     = $this->createMock(ReflectionParameter::class);
    }

    public function testCreateFromNode(): void
    {
        $typeInfo = $this->createType('string');
        self::assertInstanceOf(ReflectionType::class, $typeInfo);
    }

    public function testAllowsNull(): void
    {
        $noNullType = $this->createType('string');
        self::assertFalse($noNullType->allowsNull());

        $allowsNullType = ReflectionType::createFromNode($this->reflector, $this->owner, new NullableType(new Identifier('string')));
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
        yield ['never'];
    }

    /**
     * @dataProvider isBuildinProvider
     */
    public function testIsBuiltin(string $type): void
    {
        $reflectionType = $this->createType($type);

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
        $reflectionType = $this->createType($type);

        self::assertInstanceOf(ReflectionNamedType::class, $reflectionType);
        self::assertFalse($reflectionType->isBuiltin());
    }

    public function testImplicitCastToString(): void
    {
        self::assertSame('int', (string) $this->createType('int'));
        self::assertSame('string', (string) $this->createType('string'));
        self::assertSame('array', (string) $this->createType('array'));
        self::assertSame('callable', (string) $this->createType('callable'));
        self::assertSame('bool', (string) $this->createType('bool'));
        self::assertSame('float', (string) $this->createType('float'));
        self::assertSame('void', (string) $this->createType('void'));
        self::assertSame('object', (string) $this->createType('object'));
        self::assertSame('iterable', (string) $this->createType('iterable'));
        self::assertSame('mixed', (string) $this->createType('mixed'));
        self::assertSame('never', (string) $this->createType('never'));

        self::assertSame('Foo\Bar\Baz', (string) $this->createType('Foo\Bar\Baz'));
        self::assertSame('\Foo\Bar\Baz', (string) $this->createType('\Foo\Bar\Baz'));
    }

    private function createType(string $type): ReflectionType
    {
        return ReflectionType::createFromNode($this->reflector, $this->owner, new Identifier($type));
    }
}
