<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection\Adapter;

use OutOfBoundsException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass as CoreReflectionClass;
use ReflectionEnumUnitCase as CoreReflectionEnumUnitCase;
use Roave\BetterReflection\Reflection\Adapter\Exception\NotImplemented;
use Roave\BetterReflection\Reflection\Adapter\ReflectionAttribute as ReflectionAttributeAdapter;
use Roave\BetterReflection\Reflection\Adapter\ReflectionEnum as ReflectionEnumAdapter;
use Roave\BetterReflection\Reflection\Adapter\ReflectionEnumUnitCase as ReflectionEnumUnitCaseAdapter;
use Roave\BetterReflection\Reflection\ReflectionAttribute as BetterReflectionAttribute;
use Roave\BetterReflection\Reflection\ReflectionClass as BetterReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionEnum as BetterReflectionEnum;
use Roave\BetterReflection\Reflection\ReflectionEnumCase as BetterReflectionEnumCase;
use ValueError;

use function array_combine;
use function array_map;
use function get_class_methods;

#[CoversClass(ReflectionEnumUnitCaseAdapter::class)]
class ReflectionEnumUnitCaseTest extends TestCase
{
    /** @return array<string, array{0: string}> */
    public static function coreReflectionMethodNamesProvider(): array
    {
        $methods = get_class_methods(CoreReflectionEnumUnitCase::class);

        return array_combine($methods, array_map(static fn (string $i): array => [$i], $methods));
    }

    #[DataProvider('coreReflectionMethodNamesProvider')]
    public function testCoreReflectionMethods(string $methodName): void
    {
        $reflectionEnumUnitCaseAdapterReflection = new CoreReflectionClass(ReflectionEnumUnitCaseAdapter::class);

        self::assertTrue($reflectionEnumUnitCaseAdapterReflection->hasMethod($methodName));
        self::assertSame(ReflectionEnumUnitCaseAdapter::class, $reflectionEnumUnitCaseAdapterReflection->getMethod($methodName)->getDeclaringClass()->getName());
    }

    /** @return list<array{0: string, 1: class-string|null, 2: mixed, 3: list<mixed>}> */
    public static function methodExpectationProvider(): array
    {
        return [
            // Inherited
            ['__toString', null, '', []],
            ['getName', null, '', []],
            ['getValue', NotImplemented::class, null, []],
            ['getDocComment', null, null, []],
            ['getAttributes', null, [], []],
        ];
    }

    /** @param list<mixed> $args */
    #[DataProvider('methodExpectationProvider')]
    public function testAdapterMethods(string $methodName, string|null $expectedException, mixed $returnValue, array $args): void
    {
        $reflectionStub = $this->createMock(BetterReflectionEnumCase::class);

        if ($expectedException === null) {
            $reflectionStub->expects($this->once())
                ->method($methodName)
                ->with(...$args)
                ->willReturn($returnValue);
        }

        if ($expectedException !== null) {
            $this->expectException($expectedException);
        }

        $adapter = new ReflectionEnumUnitCaseAdapter($reflectionStub);
        $adapter->{$methodName}(...$args);
    }

    public function testHasType(): void
    {
        $betterReflectionEnumCase      = $this->createMock(BetterReflectionEnumCase::class);
        $reflectionEnumUnitCaseAdapter = new ReflectionEnumUnitCaseAdapter($betterReflectionEnumCase);

        self::assertFalse($reflectionEnumUnitCaseAdapter->hasType());
    }

    public function testGetType(): void
    {
        $betterReflectionEnumCase      = $this->createMock(BetterReflectionEnumCase::class);
        $reflectionEnumUnitCaseAdapter = new ReflectionEnumUnitCaseAdapter($betterReflectionEnumCase);

        self::assertNull($reflectionEnumUnitCaseAdapter->getType());
    }

    public function testIsPublic(): void
    {
        $betterReflectionEnumCase      = $this->createMock(BetterReflectionEnumCase::class);
        $reflectionEnumUnitCaseAdapter = new ReflectionEnumUnitCaseAdapter($betterReflectionEnumCase);

        self::assertTrue($reflectionEnumUnitCaseAdapter->isPublic());
    }

    public function testIsProtected(): void
    {
        $betterReflectionEnumCase      = $this->createMock(BetterReflectionEnumCase::class);
        $reflectionEnumUnitCaseAdapter = new ReflectionEnumUnitCaseAdapter($betterReflectionEnumCase);

        self::assertFalse($reflectionEnumUnitCaseAdapter->isProtected());
    }

    public function testIsPrivate(): void
    {
        $betterReflectionEnumCase      = $this->createMock(BetterReflectionEnumCase::class);
        $reflectionEnumUnitCaseAdapter = new ReflectionEnumUnitCaseAdapter($betterReflectionEnumCase);

        self::assertFalse($reflectionEnumUnitCaseAdapter->isPrivate());
    }

    public function testGetModifiers(): void
    {
        $betterReflectionEnumCase      = $this->createMock(BetterReflectionEnumCase::class);
        $reflectionEnumUnitCaseAdapter = new ReflectionEnumUnitCaseAdapter($betterReflectionEnumCase);

        self::assertSame(ReflectionEnumUnitCaseAdapter::IS_PUBLIC, $reflectionEnumUnitCaseAdapter->getModifiers());
    }

    public function testGetAttributes(): void
    {
        $betterReflectionAttribute1 = $this->createMock(BetterReflectionAttribute::class);
        $betterReflectionAttribute1
            ->method('getName')
            ->willReturn('SomeAttribute');
        $betterReflectionAttribute2 = $this->createMock(BetterReflectionAttribute::class);
        $betterReflectionAttribute2
            ->method('getName')
            ->willReturn('AnotherAttribute');

        $betterReflectionAttributes = [$betterReflectionAttribute1, $betterReflectionAttribute2];

        $betterReflectionEnumCase = $this->createMock(BetterReflectionEnumCase::class);
        $betterReflectionEnumCase
            ->method('getAttributes')
            ->willReturn($betterReflectionAttributes);

        $reflectionEnumUnitCaseAdapter = new ReflectionEnumUnitCaseAdapter($betterReflectionEnumCase);
        $attributes                    = $reflectionEnumUnitCaseAdapter->getAttributes();

        self::assertCount(2, $attributes);
        self::assertSame('SomeAttribute', $attributes[0]->getName());
        self::assertSame('AnotherAttribute', $attributes[1]->getName());
    }

    public function testGetAttributesWithName(): void
    {
        /** @phpstan-var class-string $someAttributeClassName */
        $someAttributeClassName = 'SomeAttribute';
        /** @phpstan-var class-string $anotherAttributeClassName */
        $anotherAttributeClassName = 'AnotherAttribute';

        $betterReflectionAttribute1 = $this->createMock(BetterReflectionAttribute::class);
        $betterReflectionAttribute1
            ->method('getName')
            ->willReturn($someAttributeClassName);
        $betterReflectionAttribute2 = $this->createMock(BetterReflectionAttribute::class);
        $betterReflectionAttribute2
            ->method('getName')
            ->willReturn($anotherAttributeClassName);

        $betterReflectionAttributes = [$betterReflectionAttribute1, $betterReflectionAttribute2];

        $betterReflectionEnumCase = $this->getMockBuilder(BetterReflectionEnumCase::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAttributes'])
            ->getMock();

        $betterReflectionEnumCase
            ->method('getAttributes')
            ->willReturn($betterReflectionAttributes);

        $reflectionEnumUnitCaseAdapter = new ReflectionEnumUnitCaseAdapter($betterReflectionEnumCase);
        $attributes                    = $reflectionEnumUnitCaseAdapter->getAttributes($someAttributeClassName);

        self::assertCount(1, $attributes);
        self::assertSame($someAttributeClassName, $attributes[0]->getName());
    }

    public function testGetAttributesWithInstance(): void
    {
        /** @phpstan-var class-string $className */
        $className = 'ClassName';
        /** @phpstan-var class-string $parentClassName */
        $parentClassName = 'ParentClassName';
        /** @phpstan-var class-string $interfaceName */
        $interfaceName = 'InterfaceName';

        $betterReflectionAttributeClass1 = $this->createMock(BetterReflectionClass::class);
        $betterReflectionAttributeClass1
            ->method('getName')
            ->willReturn($className);
        $betterReflectionAttributeClass1
            ->method('isSubclassOf')
            ->willReturnMap([
                [$parentClassName, true],
                [$interfaceName, false],
            ]);
        $betterReflectionAttributeClass1
            ->method('implementsInterface')
            ->willReturnMap([
                [$parentClassName, false],
                [$interfaceName, false],
            ]);

        $betterReflectionAttribute1 = $this->createMock(BetterReflectionAttribute::class);
        $betterReflectionAttribute1
            ->method('getClass')
            ->willReturn($betterReflectionAttributeClass1);

        $betterReflectionAttributeClass2 = $this->createMock(BetterReflectionClass::class);
        $betterReflectionAttributeClass2
            ->method('getName')
            ->willReturn('Whatever');
        $betterReflectionAttributeClass2
            ->method('isSubclassOf')
            ->willReturnMap([
                [$className, false],
                [$parentClassName, false],
                [$interfaceName, false],
            ]);
        $betterReflectionAttributeClass2
            ->method('implementsInterface')
            ->willReturnMap([
                [$className, false],
                [$parentClassName, false],
                [$interfaceName, true],
            ]);

        $betterReflectionAttribute2 = $this->createMock(BetterReflectionAttribute::class);
        $betterReflectionAttribute2
            ->method('getClass')
            ->willReturn($betterReflectionAttributeClass2);

        $betterReflectionAttributeClass3 = $this->createMock(BetterReflectionClass::class);
        $betterReflectionAttributeClass3
            ->method('getName')
            ->willReturn('Whatever');
        $betterReflectionAttributeClass3
            ->method('isSubclassOf')
            ->willReturnMap([
                [$className, false],
                [$parentClassName, true],
                [$interfaceName, false],
            ]);
        $betterReflectionAttributeClass3
            ->method('implementsInterface')
            ->willReturnMap([
                [$className, false],
                [$parentClassName, false],
                [$interfaceName, true],
            ]);

        $betterReflectionAttribute3 = $this->createMock(BetterReflectionAttribute::class);
        $betterReflectionAttribute3
            ->method('getClass')
            ->willReturn($betterReflectionAttributeClass3);

        $betterReflectionAttributes = [
            $betterReflectionAttribute1,
            $betterReflectionAttribute2,
            $betterReflectionAttribute3,
        ];

        $betterReflectionEnumCase = $this->getMockBuilder(BetterReflectionEnumCase::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAttributes'])
            ->getMock();

        $betterReflectionEnumCase
            ->method('getAttributes')
            ->willReturn($betterReflectionAttributes);

        $reflectionEnumUnitCaseAdapter = new ReflectionEnumUnitCaseAdapter($betterReflectionEnumCase);

        self::assertCount(1, $reflectionEnumUnitCaseAdapter->getAttributes($className, ReflectionAttributeAdapter::IS_INSTANCEOF));
        self::assertCount(2, $reflectionEnumUnitCaseAdapter->getAttributes($parentClassName, ReflectionAttributeAdapter::IS_INSTANCEOF));
        self::assertCount(2, $reflectionEnumUnitCaseAdapter->getAttributes($interfaceName, ReflectionAttributeAdapter::IS_INSTANCEOF));
    }

    public function testGetAttributesThrowsExceptionForInvalidFlags(): void
    {
        $betterReflectionEnumCase      = $this->createMock(BetterReflectionEnumCase::class);
        $reflectionEnumUnitCaseAdapter = new ReflectionEnumUnitCaseAdapter($betterReflectionEnumCase);

        $this->expectException(ValueError::class);
        $reflectionEnumUnitCaseAdapter->getAttributes(null, 123);
    }

    public function testIsFinal(): void
    {
        $betterReflectionEnumCase      = $this->createMock(BetterReflectionEnumCase::class);
        $reflectionEnumUnitCaseAdapter = new ReflectionEnumUnitCaseAdapter($betterReflectionEnumCase);

        self::assertTrue($reflectionEnumUnitCaseAdapter->isFinal());
    }

    public function testIsEnumCase(): void
    {
        $betterReflectionEnumCase      = $this->createMock(BetterReflectionEnumCase::class);
        $reflectionEnumUnitCaseAdapter = new ReflectionEnumUnitCaseAdapter($betterReflectionEnumCase);

        self::assertTrue($reflectionEnumUnitCaseAdapter->isEnumCase());
    }

    public function testGetDeclaringClass(): void
    {
        $betterReflectionEnum = $this->createMock(BetterReflectionEnum::class);

        $betterReflectionEnumCase = $this->createMock(BetterReflectionEnumCase::class);
        $betterReflectionEnumCase
            ->method('getDeclaringClass')
            ->willReturn($betterReflectionEnum);

        $reflectionEnumUnitCaseAdapter = new ReflectionEnumUnitCaseAdapter($betterReflectionEnumCase);

        self::assertInstanceOf(ReflectionEnumAdapter::class, $reflectionEnumUnitCaseAdapter->getEnum());
    }

    public function testGetDeclaringEnum(): void
    {
        $betterReflectionEnum = $this->createMock(BetterReflectionEnum::class);

        $betterReflectionEnumCase = $this->createMock(BetterReflectionEnumCase::class);
        $betterReflectionEnumCase
            ->method('getDeclaringEnum')
            ->willReturn($betterReflectionEnum);

        $reflectionEnumUnitCaseAdapter = new ReflectionEnumUnitCaseAdapter($betterReflectionEnumCase);

        self::assertInstanceOf(ReflectionEnumAdapter::class, $reflectionEnumUnitCaseAdapter->getEnum());
    }

    public function testPropertyName(): void
    {
        $betterReflectionEnumCase = $this->createMock(BetterReflectionEnumCase::class);
        $betterReflectionEnumCase
            ->method('getName')
            ->willReturn('FOO');

        $reflectionEnumUnitCaseAdapter = new ReflectionEnumUnitCaseAdapter($betterReflectionEnumCase);
        self::assertSame('FOO', $reflectionEnumUnitCaseAdapter->name);
    }

    public function testPropertyClass(): void
    {
        $betterReflectionClass = $this->createMock(BetterReflectionClass::class);
        $betterReflectionClass
            ->method('getName')
            ->willReturn('Foo');

        $betterReflectionEnumCase = $this->createMock(BetterReflectionEnumCase::class);
        $betterReflectionEnumCase
            ->method('getDeclaringClass')
            ->willReturn($betterReflectionClass);

        $reflectionEnumUnitCaseAdapter = new ReflectionEnumUnitCaseAdapter($betterReflectionEnumCase);
        self::assertSame('Foo', $reflectionEnumUnitCaseAdapter->class);
    }

    public function testUnknownProperty(): void
    {
        $betterReflectionEnumCase      = $this->createMock(BetterReflectionEnumCase::class);
        $reflectionEnumUnitCaseAdapter = new ReflectionEnumUnitCaseAdapter($betterReflectionEnumCase);
        $this->expectException(OutOfBoundsException::class);
        $this->expectExceptionMessage('Property Roave\BetterReflection\Reflection\Adapter\ReflectionEnumUnitCase::$foo does not exist.');
        /** @phpstan-ignore-next-line */
        $reflectionEnumUnitCaseAdapter->foo;
    }
}
