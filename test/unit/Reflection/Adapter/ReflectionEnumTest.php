<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection\Adapter;

use OutOfBoundsException;
use PHPUnit\Framework\TestCase;
use ReflectionClass as CoreReflectionClass;
use ReflectionClassConstant as CoreReflectionClassConstant;
use ReflectionEnum as CoreReflectionEnum;
use ReflectionException as CoreReflectionException;
use ReflectionMethod as CoreReflectionMethod;
use ReflectionProperty as CoreReflectionProperty;
use Roave\BetterReflection\Reflection\Adapter\Exception\NotImplemented;
use Roave\BetterReflection\Reflection\Adapter\ReflectionAttribute as ReflectionAttributeAdapter;
use Roave\BetterReflection\Reflection\Adapter\ReflectionClass as ReflectionClassAdapter;
use Roave\BetterReflection\Reflection\Adapter\ReflectionClassConstant as ReflectionClassConstantAdapter;
use Roave\BetterReflection\Reflection\Adapter\ReflectionEnum as ReflectionEnumAdapter;
use Roave\BetterReflection\Reflection\Adapter\ReflectionEnumBackedCase as ReflectionEnumBackedCaseAdapter;
use Roave\BetterReflection\Reflection\Adapter\ReflectionEnumUnitCase as ReflectionEnumUnitCaseAdapter;
use Roave\BetterReflection\Reflection\Adapter\ReflectionMethod as ReflectionMethodAdapter;
use Roave\BetterReflection\Reflection\Adapter\ReflectionNamedType as ReflectionNamedTypeAdapter;
use Roave\BetterReflection\Reflection\Adapter\ReflectionProperty as ReflectionPropertyAdapter;
use Roave\BetterReflection\Reflection\ReflectionAttribute as BetterReflectionAttribute;
use Roave\BetterReflection\Reflection\ReflectionClass as BetterReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionClassConstant as BetterReflectionClassConstant;
use Roave\BetterReflection\Reflection\ReflectionEnum as BetterReflectionEnum;
use Roave\BetterReflection\Reflection\ReflectionEnumCase as BetterReflectionEnumCase;
use Roave\BetterReflection\Reflection\ReflectionMethod as BetterReflectionMethod;
use Roave\BetterReflection\Reflection\ReflectionNamedType as BetterReflectionNamedType;
use Roave\BetterReflection\Reflection\ReflectionProperty as BetterReflectionProperty;
use Roave\BetterReflectionTest\Fixture\AutoloadableEnum;
use stdClass;
use ValueError;

use function array_combine;
use function array_map;
use function get_class_methods;
use function is_array;

/** @covers \Roave\BetterReflection\Reflection\Adapter\ReflectionEnum */
class ReflectionEnumTest extends TestCase
{
    /** @return array<string, array{0: string}> */
    public function coreReflectionMethodNamesProvider(): array
    {
        $methods = get_class_methods(CoreReflectionEnum::class);

        return array_combine($methods, array_map(static fn (string $i): array => [$i], $methods));
    }

    /** @dataProvider coreReflectionMethodNamesProvider */
    public function testCoreReflectionMethods(string $methodName): void
    {
        $reflectionEnumAdapterReflection = new CoreReflectionClass(ReflectionEnumAdapter::class);

        self::assertTrue($reflectionEnumAdapterReflection->hasMethod($methodName));
        self::assertSame(ReflectionEnumAdapter::class, $reflectionEnumAdapterReflection->getMethod($methodName)->getDeclaringClass()->getName());
    }

    /** @return list<array{0: string, 1: list<mixed>, 2: mixed, 3: string|null, 4: mixed, 5: string|null}> */
    public function methodExpectationProvider(): array
    {
        $mockClassLike = $this->createMock(BetterReflectionClass::class);

        $mockMethod = $this->createMock(BetterReflectionMethod::class);

        $mockProperty = $this->createMock(BetterReflectionProperty::class);

        $mockEnumCase = $this->createMock(BetterReflectionEnumCase::class);

        $mockAttribute = $this->createMock(BetterReflectionAttribute::class);

        return [
            // Inherited
            ['__toString', [], 'string', null, 'string', null],
            ['getName', [], 'name', null, 'name', null],
            ['isAnonymous', [], true, null, true, null],
            ['isInternal', [], true, null, true, null],
            ['isUserDefined', [], true, null, true, null],
            ['isInstantiable', [], true, null, true, null],
            ['isCloneable', [], true, null, true, null],
            ['getFileName', [], 'filename', null, 'filename', null],
            ['getStartLine', [], 123, null, 123, null],
            ['getEndLine', [], 123, null, 123, null],
            ['getDocComment', [], null, null, false, null],
            ['getConstructor', [], $mockMethod, null, null, ReflectionMethodAdapter::class],
            ['hasMethod', ['foo'], true, null, true, null],
            ['getMethod', ['foo'], $mockMethod, null, null, ReflectionMethodAdapter::class],
            ['getMethods', [], [$mockMethod], null, null, ReflectionMethodAdapter::class],
            ['hasProperty', ['foo'], true, null, true, null],
            ['getProperty', ['foo'], $mockProperty, null, null, ReflectionPropertyAdapter::class],
            ['getProperties', [], [$mockProperty], null, null, ReflectionPropertyAdapter::class],
            ['hasConstant', ['foo'], true, null, true, null],
            ['getInterfaces', [], [$mockClassLike], null, null, ReflectionClassAdapter::class],
            ['getInterfaceNames', [], ['a', 'b'], null, ['a', 'b'], null],
            ['isInterface', [], true, null, true, null],
            ['getTraits', [], [$mockClassLike], null, null, ReflectionClassAdapter::class],
            ['getTraitNames', [], ['a', 'b'], null, ['a', 'b'], null],
            ['getTraitAliases', [], ['a', 'b'], null, ['a', 'b'], null],
            ['isTrait', [], true, null, true, null],
            ['isAbstract', [], true, null, true, null],
            ['isFinal', [], true, null, true, null],
            ['isReadOnly', [], true, null, true, null],
            ['getModifiers', [], 123, null, 123, null],
            ['isInstance', [new stdClass()], true, null, true, null],
            ['newInstance', [], null, NotImplemented::class, null, null],
            ['newInstanceWithoutConstructor', [], null, NotImplemented::class, null, null],
            ['newInstanceArgs', [], null, NotImplemented::class, null, null],
            ['isSubclassOf', ['\stdClass'], true, null, true, null],
            ['getStaticProperties', [], [], null, [], null],
            ['getDefaultProperties', [], ['foo' => 'bar'], null, null, null],
            ['isIterateable', [], true, null, true, null],
            ['implementsInterface', ['\Traversable'], true, null, true, null],
            ['getExtension', [], null, NotImplemented::class, null, null],
            ['getExtensionName', [], null, null, false, null],
            ['inNamespace', [], true, null, true, null],
            ['getNamespaceName', [], '', null, '', null],
            ['getShortName', [], 'shortName', null, 'shortName', null],
            ['getAttributes', [], [$mockAttribute], null, null, ReflectionAttributeAdapter::class],
            ['isEnum', [], true, null, true, null],

            // ReflectionEnum
            ['hasCase', ['case'], false, null, false, null],
            ['getCase', ['case'], $mockEnumCase, null, null, ReflectionEnumUnitCaseAdapter::class],
            ['getCases', [], [], null, [], null],
            ['isBacked', [], false, null, false, null],
        ];
    }

    /**
     * @param list<mixed> $args
     *
     * @dataProvider methodExpectationProvider
     */
    public function testAdapterMethods(
        string $methodName,
        array $args,
        mixed $returnValue,
        string|null $expectedException,
        mixed $expectedReturnValue,
        string|null $expectedReturnValueInstance,
    ): void {
        $reflectionStub = $this->createMock(BetterReflectionEnum::class);

        if ($expectedException === null) {
            $reflectionStub->expects($this->once())
                ->method($methodName)
                ->with(...$args)
                ->willReturn($returnValue);
        }

        $adapter = new ReflectionEnumAdapter($reflectionStub);

        if ($expectedException !== null) {
            $this->expectException($expectedException);
        }

        $actualReturnValue = $adapter->{$methodName}(...$args);

        if ($expectedReturnValue !== null) {
            self::assertSame($expectedReturnValue, $actualReturnValue);
        }

        if ($expectedReturnValueInstance === null) {
            return;
        }

        if (is_array($actualReturnValue)) {
            self::assertNotEmpty($actualReturnValue);
            self::assertContainsOnlyInstancesOf($expectedReturnValueInstance, $actualReturnValue);
        } else {
            self::assertInstanceOf($expectedReturnValueInstance, $actualReturnValue);
        }
    }

    public function testIsSubclassOfIsCaseInsensitive(): void
    {
        $betterReflectionClass = $this->createMock(BetterReflectionEnum::class);
        $betterReflectionClass
            ->method('getParentClassNames')
            ->willReturn(['Foo']);
        $betterReflectionClass
            ->method('isSubclassOf')
            ->with('Foo')
            ->willReturn(true);

        $reflectionClassAdapter = new ReflectionEnumAdapter($betterReflectionClass);

        self::assertTrue($reflectionClassAdapter->isSubclassOf('Foo'));
        self::assertTrue($reflectionClassAdapter->isSubclassOf('foo'));
        self::assertTrue($reflectionClassAdapter->isSubclassOf('FoO'));
    }

    public function testImplementsInterfaceIsCaseInsensitive(): void
    {
        $betterReflectionClass = $this->createMock(BetterReflectionEnum::class);
        $betterReflectionClass
            ->method('getInterfaceNames')
            ->willReturn(['Foo']);
        $betterReflectionClass
            ->method('implementsInterface')
            ->with('Foo')
            ->willReturn(true);

        $reflectionClassAdapter = new ReflectionEnumAdapter($betterReflectionClass);

        self::assertTrue($reflectionClassAdapter->implementsInterface('Foo'));
        self::assertTrue($reflectionClassAdapter->implementsInterface('foo'));
        self::assertTrue($reflectionClassAdapter->implementsInterface('FoO'));
    }

    public function testPropertyName(): void
    {
        $betterReflectionEnum = $this->createMock(BetterReflectionEnum::class);
        $betterReflectionEnum
            ->method('getName')
            ->willReturn('Foo');

        $reflectionEnumAdapter = new ReflectionEnumAdapter($betterReflectionEnum);

        self::assertSame('Foo', $reflectionEnumAdapter->name);
    }

    public function testUnknownProperty(): void
    {
        $betterReflectionEnum  = $this->createMock(BetterReflectionEnum::class);
        $reflectionEnumAdapter = new ReflectionEnumAdapter($betterReflectionEnum);

        $this->expectException(OutOfBoundsException::class);
        $this->expectExceptionMessage('Property Roave\BetterReflection\Reflection\Adapter\ReflectionEnum::$foo does not exist.');
        /** @phpstan-ignore-next-line */
        $reflectionEnumAdapter->foo;
    }

    public function testGetConstructorReturnsNullWhenNoConstructorExists(): void
    {
        $betterReflectionEnum = $this->createMock(BetterReflectionEnum::class);
        $betterReflectionEnum
            ->method('getConstructor')
            ->willReturn(null);

        $reflectionEnumAdapter = new ReflectionEnumAdapter($betterReflectionEnum);

        self::assertNull($reflectionEnumAdapter->getConstructor());
    }

    public function testGetPropertyThrowsExceptionWhenPropertyDoesNotExist(): void
    {
        $betterReflectionEnum = $this->createMock(BetterReflectionEnum::class);
        $betterReflectionEnum
            ->method('getProperty')
            ->with('foo')
            ->willReturn(null);

        $reflectionEnumAdapter = new ReflectionEnumAdapter($betterReflectionEnum);

        $this->expectException(CoreReflectionException::class);
        $reflectionEnumAdapter->getProperty('foo');
    }

    public function testGetConstantsWithFilter(): void
    {
        $betterReflectionEnum                   = $this->createMock(BetterReflectionEnum::class);
        $publicBetterReflectionClassConstant    = $this->createMock(BetterReflectionClassConstant::class);
        $privateBetterReflectionClassConstant   = $this->createMock(BetterReflectionClassConstant::class);
        $protectedBetterReflectionClassConstant = $this->createMock(BetterReflectionClassConstant::class);

        $publicBetterReflectionClassConstant
            ->method('getName')
            ->willReturn('PUBLIC_CONSTANT');

        $publicBetterReflectionClassConstant
            ->method('getValue')
            ->willReturn('public constant');

        $privateBetterReflectionClassConstant
            ->method('getName')
            ->willReturn('PRIVATE_CONSTANT');

        $privateBetterReflectionClassConstant
            ->method('getValue')
            ->willReturn('private constant');

        $protectedBetterReflectionClassConstant
            ->method('getName')
            ->willReturn('PROTECTED_CONSTANT');

        $protectedBetterReflectionClassConstant
            ->method('getValue')
            ->willReturn('protected constant');

        $betterReflectionEnum
            ->method('getConstants')
            ->willReturnMap([
                [
                    0,
                    [
                        $publicBetterReflectionClassConstant->getName() => $publicBetterReflectionClassConstant,
                        $privateBetterReflectionClassConstant->getName() => $privateBetterReflectionClassConstant,
                        $protectedBetterReflectionClassConstant->getName() => $protectedBetterReflectionClassConstant,
                    ],
                ],
                [CoreReflectionClassConstant::IS_PUBLIC, [$publicBetterReflectionClassConstant->getName() => $publicBetterReflectionClassConstant]],
                [CoreReflectionClassConstant::IS_PRIVATE, [$privateBetterReflectionClassConstant->getName() => $privateBetterReflectionClassConstant]],
                [CoreReflectionClassConstant::IS_PROTECTED, [$protectedBetterReflectionClassConstant->getName() => $protectedBetterReflectionClassConstant]],
            ]);

        $reflectionEnumAdapter = new ReflectionEnumAdapter($betterReflectionEnum);

        $allConstants       = $reflectionEnumAdapter->getConstants();
        $publicConstants    = $reflectionEnumAdapter->getConstants(CoreReflectionClassConstant::IS_PUBLIC);
        $privateConstants   = $reflectionEnumAdapter->getConstants(CoreReflectionClassConstant::IS_PRIVATE);
        $protectedConstants = $reflectionEnumAdapter->getConstants(CoreReflectionClassConstant::IS_PROTECTED);

        self::assertCount(3, $allConstants);

        self::assertCount(1, $publicConstants);
        self::assertEquals([$publicBetterReflectionClassConstant->getName() => $publicBetterReflectionClassConstant->getValue()], $publicConstants);

        self::assertCount(1, $privateConstants);
        self::assertEquals([$privateBetterReflectionClassConstant->getName() => $privateBetterReflectionClassConstant->getValue()], $privateConstants);

        self::assertCount(1, $protectedConstants);
        self::assertEquals([$protectedBetterReflectionClassConstant->getName() => $protectedBetterReflectionClassConstant->getValue()], $protectedConstants);
    }

    public function testGetReflectionConstant(): void
    {
        $betterReflectionEnum = $this->createMock(BetterReflectionEnum::class);
        $betterReflectionEnum
            ->method('getConstant')
            ->with('FOO')
            ->willReturn($this->createMock(BetterReflectionClassConstant::class));

        $reflectionEnumAdapter = new ReflectionEnumAdapter($betterReflectionEnum);

        self::assertInstanceOf(ReflectionClassConstantAdapter::class, $reflectionEnumAdapter->getReflectionConstant('FOO'));
    }

    public function testGetReflectionConstantReturnsFalseWhenConstantDoesNotExist(): void
    {
        $betterReflectionEnum = $this->createMock(BetterReflectionEnum::class);
        $betterReflectionEnum
            ->method('getConstant')
            ->with('FOO')
            ->willReturn(null);

        $reflectionEnumAdapter = new ReflectionEnumAdapter($betterReflectionEnum);

        self::assertFalse($reflectionEnumAdapter->getReflectionConstant('FOO'));
    }

    public function testGetParentClassReturnsFalse(): void
    {
        $betterReflectionEnum  = $this->createMock(BetterReflectionEnum::class);
        $reflectionEnumAdapter = new ReflectionEnumAdapter($betterReflectionEnum);

        self::assertFalse($reflectionEnumAdapter->getParentClass());
    }

    public function testGetStaticPropertyThrowsException(): void
    {
        $betterReflectionEnum  = $this->createMock(BetterReflectionEnum::class);
        $reflectionEnumAdapter = new ReflectionEnumAdapter($betterReflectionEnum);

        $this->expectException(CoreReflectionException::class);
        $reflectionEnumAdapter->getStaticPropertyValue('foo');
    }

    public function testSetStaticPropertyValueThrowsExceptionWhenPropertyDoesNotExist(): void
    {
        $betterReflectionEnum  = $this->createMock(BetterReflectionEnum::class);
        $reflectionEnumAdapter = new ReflectionEnumAdapter($betterReflectionEnum);

        $this->expectException(CoreReflectionException::class);
        $reflectionEnumAdapter->setStaticPropertyValue('foo', null);
    }

    public function testIsIterable(): void
    {
        $betterReflectionEnum = $this->createMock(BetterReflectionEnum::class);
        $betterReflectionEnum
            ->method('isIterateable')
            ->willReturn(true);

        $reflectionEnumAdapter = new ReflectionEnumAdapter($betterReflectionEnum);

        self::assertTrue($reflectionEnumAdapter->isIterable());
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

        $betterReflectionEnum = $this->createMock(BetterReflectionEnum::class);
        $betterReflectionEnum
            ->method('getAttributes')
            ->willReturn($betterReflectionAttributes);

        $reflectionEnumAdapter = new ReflectionEnumAdapter($betterReflectionEnum);
        $attributes            = $reflectionEnumAdapter->getAttributes();

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

        $betterReflectionEnum = $this->getMockBuilder(BetterReflectionEnum::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAttributes'])
            ->getMock();

        $betterReflectionEnum
            ->method('getAttributes')
            ->willReturn($betterReflectionAttributes);

        $reflectionEnumAdapter = new ReflectionEnumAdapter($betterReflectionEnum);
        $attributes            = $reflectionEnumAdapter->getAttributes($someAttributeClassName);

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

        $betterReflectionEnum = $this->getMockBuilder(BetterReflectionEnum::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAttributes'])
            ->getMock();

        $betterReflectionEnum
            ->method('getAttributes')
            ->willReturn($betterReflectionAttributes);

        $reflectionEnumAdapter = new ReflectionEnumAdapter($betterReflectionEnum);

        self::assertCount(1, $reflectionEnumAdapter->getAttributes($className, ReflectionAttributeAdapter::IS_INSTANCEOF));
        self::assertCount(2, $reflectionEnumAdapter->getAttributes($parentClassName, ReflectionAttributeAdapter::IS_INSTANCEOF));
        self::assertCount(2, $reflectionEnumAdapter->getAttributes($interfaceName, ReflectionAttributeAdapter::IS_INSTANCEOF));
    }

    public function testGetAttributesThrowsExceptionForInvalidFlags(): void
    {
        $betterReflectionEnum  = $this->createMock(BetterReflectionEnum::class);
        $reflectionEnumAdapter = new ReflectionEnumAdapter($betterReflectionEnum);

        self::expectException(ValueError::class);
        $reflectionEnumAdapter->getAttributes(null, 123);
    }

    public function testGetCaseWhenCaseDoesNotExist(): void
    {
        $betterReflectionEnum = $this->createMock(BetterReflectionEnum::class);
        $betterReflectionEnum
            ->method('getCase')
            ->willReturn(null);

        $reflectionEnumAdapter = new ReflectionEnumAdapter($betterReflectionEnum);

        self::expectException(CoreReflectionException::class);
        $reflectionEnumAdapter->getCase('case');
    }

    public function testGetCaseForPureEnum(): void
    {
        $betterReflectionEnumCase = $this->createMock(BetterReflectionEnumCase::class);

        $betterReflectionEnum = $this->createMock(BetterReflectionEnum::class);
        $betterReflectionEnum
            ->method('isBacked')
            ->willReturn(false);
        $betterReflectionEnum
            ->method('getCase')
            ->willReturn($betterReflectionEnumCase);

        $reflectionEnumAdapter = new ReflectionEnumAdapter($betterReflectionEnum);

        self::assertInstanceOf(ReflectionEnumUnitCaseAdapter::class, $reflectionEnumAdapter->getCase('case'));
    }

    public function testGetCaseForBackedEnum(): void
    {
        $betterReflectionEnumCase = $this->createMock(BetterReflectionEnumCase::class);

        $betterReflectionEnum = $this->createMock(BetterReflectionEnum::class);
        $betterReflectionEnum
            ->method('isBacked')
            ->willReturn(true);
        $betterReflectionEnum
            ->method('getCase')
            ->willReturn($betterReflectionEnumCase);

        $reflectionEnumAdapter = new ReflectionEnumAdapter($betterReflectionEnum);

        self::assertInstanceOf(ReflectionEnumBackedCaseAdapter::class, $reflectionEnumAdapter->getCase('case'));
    }

    public function testGetCasesForPureEnum(): void
    {
        $betterReflectionEnumCase = $this->createMock(BetterReflectionEnumCase::class);

        $betterReflectionEnum = $this->createMock(BetterReflectionEnum::class);
        $betterReflectionEnum
            ->method('isBacked')
            ->willReturn(false);
        $betterReflectionEnum
            ->method('getCases')
            ->willReturn([$betterReflectionEnumCase]);

        $reflectionEnumAdapter = new ReflectionEnumAdapter($betterReflectionEnum);

        self::assertContainsOnlyInstancesOf(ReflectionEnumUnitCaseAdapter::class, $reflectionEnumAdapter->getCases());
    }

    public function testGetCasesForBackedEnum(): void
    {
        $betterReflectionEnumCase = $this->createMock(BetterReflectionEnumCase::class);

        $betterReflectionEnum = $this->createMock(BetterReflectionEnum::class);
        $betterReflectionEnum
            ->method('isBacked')
            ->willReturn(true);
        $betterReflectionEnum
            ->method('getCases')
            ->willReturn([$betterReflectionEnumCase]);

        $reflectionEnumAdapter = new ReflectionEnumAdapter($betterReflectionEnum);

        self::assertContainsOnlyInstancesOf(ReflectionEnumBackedCaseAdapter::class, $reflectionEnumAdapter->getCases());
    }

    public function testGetBackingTypeForPureEnum(): void
    {
        $betterReflectionEnum = $this->createMock(BetterReflectionEnum::class);
        $betterReflectionEnum
            ->method('isBacked')
            ->willReturn(false);

        $reflectionEnumAdapter = new ReflectionEnumAdapter($betterReflectionEnum);

        self::assertNull($reflectionEnumAdapter->getBackingType());
    }

    public function testGetBackingTypeForBackedEnum(): void
    {
        $betterReflectionNamedType = $this->createMock(BetterReflectionNamedType::class);

        $betterReflectionEnum = $this->createMock(BetterReflectionEnum::class);
        $betterReflectionEnum
            ->method('isBacked')
            ->willReturn(true);
        $betterReflectionEnum
            ->method('getBackingType')
            ->willReturn($betterReflectionNamedType);

        $reflectionEnumAdapter = new ReflectionEnumAdapter($betterReflectionEnum);
        $backingType           = $reflectionEnumAdapter->getBackingType();

        self::assertInstanceOf(ReflectionNamedTypeAdapter::class, $backingType);
        self::assertFalse($backingType->allowsNull());
    }

    public function testHasConstantWithEnumCase(): void
    {
        $betterReflectionEnum = $this->createMock(BetterReflectionEnum::class);
        $betterReflectionEnum
            ->method('hasCase')
            ->with('ENUM_CASE')
            ->willReturn(true);

        $reflectionClassAdapter = new ReflectionEnumAdapter($betterReflectionEnum);

        self::assertTrue($reflectionClassAdapter->hasConstant('ENUM_CASE'));
    }

    /**
     * @runInSeparateProcess
     * @requires PHP >= 8.1
     */
    public function testGetConstantWithEnumCase(): void
    {
        $betterReflectionEnumCase = $this->createMock(BetterReflectionEnumCase::class);
        $betterReflectionEnumCase
            ->method('getName')
            ->willReturn('ENUM_CASE');

        $betterReflectionEnum = $this->createMock(BetterReflectionEnum::class);
        $betterReflectionEnum
            ->method('getName')
            ->willReturn(AutoloadableEnum::class);
        $betterReflectionEnum
            ->method('hasCase')
            ->with('ENUM_CASE')
            ->willReturn(true);
        $betterReflectionEnum
            ->method('getCase')
            ->with('ENUM_CASE')
            ->willReturn($betterReflectionEnumCase);

        $betterReflectionEnumCase
            ->method('getDeclaringClass')
            ->willReturn($betterReflectionEnum);

        $reflectionClassAdapter = new ReflectionEnumAdapter($betterReflectionEnum);

        self::assertInstanceOf(AutoloadableEnum::class, $reflectionClassAdapter->getConstant('ENUM_CASE'));
    }

    public function testGetReflectionConstantWithEnumCase(): void
    {
        $betterReflectionEnumCase = $this->createMock(BetterReflectionEnumCase::class);

        $betterReflectionEnum = $this->createMock(BetterReflectionEnum::class);
        $betterReflectionEnum
            ->method('hasCase')
            ->with('ENUM_CASE')
            ->willReturn(true);
        $betterReflectionEnum
            ->method('getCase')
            ->with('ENUM_CASE')
            ->willReturn($betterReflectionEnumCase);

        $reflectionClassAdapter = new ReflectionEnumAdapter($betterReflectionEnum);

        self::assertInstanceOf(ReflectionClassConstantAdapter::class, $reflectionClassAdapter->getReflectionConstant('ENUM_CASE'));
    }

    public function testGetReflectionConstantsWithFilterAndEnumCase(): void
    {
        $betterReflectionEnum                   = $this->createMock(BetterReflectionEnum::class);
        $betterReflectionEnumCase               = $this->createMock(BetterReflectionEnumCase::class);
        $publicBetterReflectionClassConstant    = $this->createMock(BetterReflectionClassConstant::class);
        $privateBetterReflectionClassConstant   = $this->createMock(BetterReflectionClassConstant::class);
        $protectedBetterReflectionClassConstant = $this->createMock(BetterReflectionClassConstant::class);

        $publicBetterReflectionClassConstant
            ->method('getName')
            ->willReturn('PUBLIC_CONSTANT');

        $privateBetterReflectionClassConstant
            ->method('getName')
            ->willReturn('PRIVATE_CONSTANT');

        $protectedBetterReflectionClassConstant
            ->method('getName')
            ->willReturn('PROTECTED_CONSTANT');

        $betterReflectionEnum
            ->method('getCases')
            ->willReturn(['enum_case' => $betterReflectionEnumCase]);

        $betterReflectionEnum
            ->method('getConstants')
            ->willReturnMap([
                [
                    0,
                    [
                        $publicBetterReflectionClassConstant->getName() => $publicBetterReflectionClassConstant,
                        $privateBetterReflectionClassConstant->getName() => $privateBetterReflectionClassConstant,
                        $protectedBetterReflectionClassConstant->getName() => $protectedBetterReflectionClassConstant,
                    ],
                ],
                [CoreReflectionClassConstant::IS_PUBLIC, [$publicBetterReflectionClassConstant->getName() => $publicBetterReflectionClassConstant]],
                [CoreReflectionClassConstant::IS_PRIVATE, [$privateBetterReflectionClassConstant->getName() => $privateBetterReflectionClassConstant]],
                [CoreReflectionClassConstant::IS_PROTECTED, [$protectedBetterReflectionClassConstant->getName() => $protectedBetterReflectionClassConstant]],
            ]);

        $reflectionClassAdapter = new ReflectionEnumAdapter($betterReflectionEnum);

        self::assertCount(4, $reflectionClassAdapter->getReflectionConstants());
        self::assertCount(2, $reflectionClassAdapter->getReflectionConstants(CoreReflectionClassConstant::IS_PUBLIC));
        self::assertCount(1, $reflectionClassAdapter->getReflectionConstants(CoreReflectionClassConstant::IS_PRIVATE));
        self::assertCount(1, $reflectionClassAdapter->getReflectionConstants(CoreReflectionClassConstant::IS_PROTECTED));
    }

    public function testGetTraits(): void
    {
        /** @phpstan-var class-string $traitOneClassName */
        $traitOneClassName = 'Trait1';
        /** @phpstan-var class-string $traitTwoClassName */
        $traitTwoClassName = 'Trait2';

        $betterReflectionTrait1 = $this->createMock(BetterReflectionClass::class);
        $betterReflectionTrait1
            ->method('getName')
            ->willReturn($traitOneClassName);
        $betterReflectionTrait2 = $this->createMock(BetterReflectionClass::class);
        $betterReflectionTrait2
            ->method('getName')
            ->willReturn($traitTwoClassName);

        $betterReflectioEnum = $this->createMock(BetterReflectionEnum::class);
        $betterReflectioEnum
            ->method('getTraits')
            ->willReturn([$betterReflectionTrait1, $betterReflectionTrait2]);

        $reflectionEnumAdapter = new ReflectionEnumAdapter($betterReflectioEnum);

        $traits = $reflectionEnumAdapter->getTraits();

        self::assertContainsOnlyInstancesOf(ReflectionClassAdapter::class, $traits);
        self::assertCount(2, $traits);
        self::assertArrayHasKey($traitOneClassName, $traits);
        self::assertArrayHasKey($traitTwoClassName, $traits);
    }

    public function testGetMethodThrowsExceptionWhenMethodDoesNotExist(): void
    {
        $betterReflectionEnum = $this->createMock(BetterReflectionEnum::class);
        $betterReflectionEnum
            ->method('getName')
            ->willReturn('SomeClass');

        $reflectionEnumAdapter = new ReflectionEnumAdapter($betterReflectionEnum);

        $this->expectException(CoreReflectionException::class);
        $this->expectExceptionMessage('Method SomeClass::doesNotExist() does not exist');
        $reflectionEnumAdapter->getMethod('doesNotExist');
    }

    public function testGetMethodsWithFilter(): void
    {
        $betterReflectionEnum            = $this->createMock(BetterReflectionEnum::class);
        $publicBetterReflectionMethod    = $this->createMock(BetterReflectionMethod::class);
        $privateBetterReflectionMethod   = $this->createMock(BetterReflectionMethod::class);
        $protectedBetterReflectionMethod = $this->createMock(BetterReflectionMethod::class);

        $publicBetterReflectionMethod
            ->method('getName')
            ->willReturn('public');

        $privateBetterReflectionMethod
            ->method('getName')
            ->willReturn('private');

        $protectedBetterReflectionMethod
            ->method('getName')
            ->willReturn('protected');

        $betterReflectionEnum
            ->method('getMethods')
            ->willReturnMap([
                [
                    0,
                    [
                        $publicBetterReflectionMethod->getName() => $publicBetterReflectionMethod,
                        $privateBetterReflectionMethod->getName() => $privateBetterReflectionMethod,
                        $protectedBetterReflectionMethod->getName() => $protectedBetterReflectionMethod,
                    ],
                ],
                [CoreReflectionMethod::IS_PUBLIC, [$publicBetterReflectionMethod->getName() => $publicBetterReflectionMethod]],
                [CoreReflectionMethod::IS_PRIVATE, [$privateBetterReflectionMethod->getName() => $privateBetterReflectionMethod]],
                [CoreReflectionMethod::IS_PROTECTED, [$protectedBetterReflectionMethod->getName() => $protectedBetterReflectionMethod]],
            ]);

        $reflectionEnumAdapter = new ReflectionEnumAdapter($betterReflectionEnum);

        self::assertCount(3, $reflectionEnumAdapter->getMethods());
        self::assertCount(1, $reflectionEnumAdapter->getMethods(CoreReflectionMethod::IS_PUBLIC));
        self::assertCount(1, $reflectionEnumAdapter->getMethods(CoreReflectionMethod::IS_PRIVATE));
        self::assertCount(1, $reflectionEnumAdapter->getMethods(CoreReflectionMethod::IS_PROTECTED));
    }

    public function testGetPropertiesWithFilter(): void
    {
        $betterReflectionEnum              = $this->createMock(BetterReflectionEnum::class);
        $publicBetterReflectionProperty    = $this->createMock(BetterReflectionProperty::class);
        $privateBetterReflectionProperty   = $this->createMock(BetterReflectionProperty::class);
        $protectedBetterReflectionProperty = $this->createMock(BetterReflectionProperty::class);

        $publicBetterReflectionProperty
            ->method('getName')
            ->willReturn('public');

        $privateBetterReflectionProperty
            ->method('getName')
            ->willReturn('private');

        $protectedBetterReflectionProperty
            ->method('getName')
            ->willReturn('protected');

        $betterReflectionEnum
            ->method('getProperties')
            ->willReturnMap([
                [
                    0,
                    [
                        $publicBetterReflectionProperty->getName() => $publicBetterReflectionProperty,
                        $privateBetterReflectionProperty->getName() => $privateBetterReflectionProperty,
                        $protectedBetterReflectionProperty->getName() => $protectedBetterReflectionProperty,
                    ],
                ],
                [CoreReflectionProperty::IS_PUBLIC, [$publicBetterReflectionProperty->getName() => $publicBetterReflectionProperty]],
                [CoreReflectionProperty::IS_PRIVATE, [$privateBetterReflectionProperty->getName() => $privateBetterReflectionProperty]],
                [CoreReflectionProperty::IS_PROTECTED, [$protectedBetterReflectionProperty->getName() => $protectedBetterReflectionProperty]],
            ]);

        $reflectionEnumAdapter = new ReflectionEnumAdapter($betterReflectionEnum);

        self::assertCount(3, $reflectionEnumAdapter->getProperties());
        self::assertCount(1, $reflectionEnumAdapter->getProperties(CoreReflectionProperty::IS_PUBLIC));
        self::assertCount(1, $reflectionEnumAdapter->getProperties(CoreReflectionProperty::IS_PRIVATE));
        self::assertCount(1, $reflectionEnumAdapter->getProperties(CoreReflectionProperty::IS_PROTECTED));
    }
}
