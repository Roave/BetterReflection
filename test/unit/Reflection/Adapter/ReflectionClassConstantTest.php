<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection\Adapter;

use OutOfBoundsException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass as CoreReflectionClass;
use ReflectionClassConstant as CoreReflectionClassConstant;
use Roave\BetterReflection\Reflection\Adapter\ReflectionAttribute as ReflectionAttributeAdapter;
use Roave\BetterReflection\Reflection\Adapter\ReflectionClass as ReflectionClassAdapter;
use Roave\BetterReflection\Reflection\Adapter\ReflectionClassConstant as ReflectionClassConstantAdapter;
use Roave\BetterReflection\Reflection\ReflectionAttribute as BetterReflectionAttribute;
use Roave\BetterReflection\Reflection\ReflectionClass as BetterReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionClassConstant as BetterReflectionClassConstant;
use Roave\BetterReflection\Reflection\ReflectionEnum as BetterReflectionEnum;
use Roave\BetterReflection\Reflection\ReflectionEnumCase as BetterReflectionEnumCase;
use Roave\BetterReflectionTest\Fixture\PureEnum;
use ValueError;

use function array_combine;
use function array_map;
use function get_class_methods;

#[CoversClass(ReflectionClassConstantAdapter::class)]
class ReflectionClassConstantTest extends TestCase
{
    /** @return array<string, array{0: string}> */
    public static function coreReflectionMethodNamesProvider(): array
    {
        $methods = get_class_methods(CoreReflectionClassConstant::class);

        return array_combine($methods, array_map(static fn (string $i): array => [$i], $methods));
    }

    #[DataProvider('coreReflectionMethodNamesProvider')]
    public function testCoreReflectionMethods(string $methodName): void
    {
        $reflectionClassConstantAdapterReflection = new CoreReflectionClass(ReflectionClassConstantAdapter::class);

        self::assertTrue($reflectionClassConstantAdapterReflection->hasMethod($methodName));
        self::assertSame(ReflectionClassConstantAdapter::class, $reflectionClassConstantAdapterReflection->getMethod($methodName)->getDeclaringClass()->getName());
    }

    /** @return list<array{0: string, 1: class-string|null, 2: mixed, 3: list<mixed>}> */
    public static function methodExpectationProvider(): array
    {
        return [
            ['__toString', null, '', []],
            ['getName', null, '', []],
            ['hasType', null, false, []],
            ['getType', null, null, []],
            ['getValue', null, null, []],
            ['isPublic', null, true, []],
            ['isPrivate', null, true, []],
            ['isProtected', null, true, []],
            ['getModifiers', null, 123, []],
            ['getDocComment', null, null, []],
            ['getAttributes', null, [], []],
            ['isFinal', null, true, []],
        ];
    }

    /** @param list<mixed> $args */
    #[DataProvider('methodExpectationProvider')]
    public function testAdapterMethods(string $methodName, string|null $expectedException, mixed $returnValue, array $args): void
    {
        $reflectionStub = $this->createMock(BetterReflectionClassConstant::class);

        if ($expectedException === null) {
            $reflectionStub->expects($this->once())
                ->method($methodName)
                ->with(...$args)
                ->willReturn($returnValue);
        }

        if ($expectedException !== null) {
            $this->expectException($expectedException);
        }

        $adapter = new ReflectionClassConstantAdapter($reflectionStub);
        $adapter->{$methodName}(...$args);
    }

    /** @return list<array{0: string, 1: mixed}> */
    public static function dataAdapterMethodsForEnumCase(): array
    {
        return [
            ['isPublic', true],
            ['isProtected', false],
            ['isPrivate', false],
            ['getModifiers', 1],
            ['isFinal', true],
        ];
    }

    #[DataProvider('dataAdapterMethodsForEnumCase')]
    public function testAdapterMethodsForEnumCase(string $methodName, mixed $expectedValue): void
    {
        $reflectionClassConstantAdapter = new ReflectionClassConstantAdapter($this->createMock(BetterReflectionEnumCase::class));

        self::assertSame($expectedValue, $reflectionClassConstantAdapter->{$methodName}());
    }

    public function testHasTypeForEnumCase(): void
    {
        $reflectionClassConstantAdapter = new ReflectionClassConstantAdapter($this->createMock(BetterReflectionEnumCase::class));

        self::assertFalse($reflectionClassConstantAdapter->hasType());
    }

    public function testGetTypeForEnumCase(): void
    {
        $reflectionClassConstantAdapter = new ReflectionClassConstantAdapter($this->createMock(BetterReflectionEnumCase::class));

        self::assertNull($reflectionClassConstantAdapter->getType());
    }

    public function testGetValueForEnumCase(): void
    {
        require_once __DIR__ . '/../../Fixture/Enums.php';

        $reflectionClassAdapter = $this->createMock(BetterReflectionClass::class);
        $reflectionClassAdapter
            ->method('getName')
            ->willReturn(PureEnum::class);

        $reflectionEnumCaseAdapter = $this->createMock(BetterReflectionEnumCase::class);
        $reflectionEnumCaseAdapter
            ->method('getDeclaringClass')
            ->willReturn($reflectionClassAdapter);
        $reflectionEnumCaseAdapter
            ->method('getName')
            ->willReturn('ONE');

        $reflectionClassConstantAdapter = new ReflectionClassConstantAdapter($reflectionEnumCaseAdapter);

        self::assertInstanceOf(PureEnum::class, $reflectionClassConstantAdapter->getValue());
    }

    public function testGetDocCommentReturnsFalseWhenNoDocComment(): void
    {
        $betterReflectionClassConstant = $this->createMock(BetterReflectionClassConstant::class);
        $betterReflectionClassConstant
            ->method('getDocComment')
            ->willReturn(null);

        $reflectionClassConstantAdapter = new ReflectionClassConstantAdapter($betterReflectionClassConstant);

        self::assertFalse($reflectionClassConstantAdapter->getDocComment());
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

        $betterReflectionClassConstant = $this->createMock(BetterReflectionClassConstant::class);
        $betterReflectionClassConstant
            ->method('getAttributes')
            ->willReturn($betterReflectionAttributes);

        $reflectionClassConstantAdapter = new ReflectionClassConstantAdapter($betterReflectionClassConstant);
        $attributes                     = $reflectionClassConstantAdapter->getAttributes();

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

        $betterReflectionClassConstant = $this->getMockBuilder(BetterReflectionClassConstant::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAttributes'])
            ->getMock();

        $betterReflectionClassConstant
            ->method('getAttributes')
            ->willReturn($betterReflectionAttributes);

        $reflectionClassAdapter = new ReflectionClassConstantAdapter($betterReflectionClassConstant);
        $attributes             = $reflectionClassAdapter->getAttributes($someAttributeClassName);

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

        $betterReflectionClassConstant = $this->getMockBuilder(BetterReflectionClassConstant::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAttributes'])
            ->getMock();

        $betterReflectionClassConstant
            ->method('getAttributes')
            ->willReturn($betterReflectionAttributes);

        $reflectionClassConstantAdapter = new ReflectionClassConstantAdapter($betterReflectionClassConstant);

        self::assertCount(1, $reflectionClassConstantAdapter->getAttributes($className, ReflectionAttributeAdapter::IS_INSTANCEOF));
        self::assertCount(2, $reflectionClassConstantAdapter->getAttributes($parentClassName, ReflectionAttributeAdapter::IS_INSTANCEOF));
        self::assertCount(2, $reflectionClassConstantAdapter->getAttributes($interfaceName, ReflectionAttributeAdapter::IS_INSTANCEOF));
    }

    public function testGetAttributesThrowsExceptionForInvalidFlags(): void
    {
        $betterReflectionClassConstant  = $this->createMock(BetterReflectionClassConstant::class);
        $reflectionClassConstantAdapter = new ReflectionClassConstantAdapter($betterReflectionClassConstant);

        $this->expectException(ValueError::class);
        $reflectionClassConstantAdapter->getAttributes(null, 123);
    }

    public function testIsEnumCaseWithClassConstant(): void
    {
        $reflectionClassConstantAdapter = new ReflectionClassConstantAdapter($this->createMock(BetterReflectionClassConstant::class));

        self::assertFalse($reflectionClassConstantAdapter->isEnumCase());
    }

    public function testIsEnumCaseWithEnumCase(): void
    {
        $reflectionClassConstantAdapter = new ReflectionClassConstantAdapter($this->createMock(BetterReflectionEnumCase::class));

        self::assertTrue($reflectionClassConstantAdapter->isEnumCase());
    }

    public function testPropertyName(): void
    {
        $betterReflectionClassConstant = $this->createMock(BetterReflectionClassConstant::class);
        $betterReflectionClassConstant
            ->method('getName')
            ->willReturn('FOO');

        $reflectionClassConstantAdapter = new ReflectionClassConstantAdapter($betterReflectionClassConstant);
        self::assertSame('FOO', $reflectionClassConstantAdapter->name);
    }

    public function testPropertyClass(): void
    {
        $betterReflectionClass = $this->createMock(BetterReflectionClass::class);
        $betterReflectionClass
            ->method('getName')
            ->willReturn('Foo');

        $betterReflectionClassConstant = $this->createMock(BetterReflectionClassConstant::class);
        $betterReflectionClassConstant
            ->method('getImplementingClass')
            ->willReturn($betterReflectionClass);

        $reflectionClassConstantAdapter = new ReflectionClassConstantAdapter($betterReflectionClassConstant);
        self::assertSame('Foo', $reflectionClassConstantAdapter->class);
    }

    public function testUnknownProperty(): void
    {
        $betterReflectionClassConstant  = $this->createMock(BetterReflectionClassConstant::class);
        $reflectionClassConstantAdapter = new ReflectionClassConstantAdapter($betterReflectionClassConstant);
        $this->expectException(OutOfBoundsException::class);
        $this->expectExceptionMessage('Property Roave\BetterReflection\Reflection\Adapter\ReflectionClassConstant::$foo does not exist.');
        /** @phpstan-ignore-next-line */
        $reflectionClassConstantAdapter->foo;
    }

    public function testGetDeclaringClassForClass(): void
    {
        $betterReflectionClass = $this->createMock(BetterReflectionClass::class);
        $betterReflectionClass
            ->method('getName')
            ->willReturn('DeclaringClass');

        $betterReflectionClassConstant = $this->createMock(BetterReflectionClassConstant::class);
        $betterReflectionClassConstant
            ->method('getImplementingClass')
            ->willReturn($betterReflectionClass);

        $reflectionClassConstantAdapter = new ReflectionClassConstantAdapter($betterReflectionClassConstant);

        self::assertInstanceOf(ReflectionClassAdapter::class, $reflectionClassConstantAdapter->getDeclaringClass());
        self::assertSame('DeclaringClass', $reflectionClassConstantAdapter->getDeclaringClass()->getName());
    }

    public function testGetDeclaringClassForEnum(): void
    {
        $betterReflectionEnum = $this->createMock(BetterReflectionEnum::class);
        $betterReflectionEnum
            ->method('getName')
            ->willReturn('DeclaringEnum');

        $betterReflectionEnumCase = $this->createMock(BetterReflectionEnumCase::class);
        $betterReflectionEnumCase
            ->method('getDeclaringClass')
            ->willReturn($betterReflectionEnum);

        $reflectionClassConstantAdapter = new ReflectionClassConstantAdapter($betterReflectionEnumCase);

        self::assertInstanceOf(ReflectionClassAdapter::class, $reflectionClassConstantAdapter->getDeclaringClass());
        self::assertSame('DeclaringEnum', $reflectionClassConstantAdapter->getDeclaringClass()->getName());
    }
}
