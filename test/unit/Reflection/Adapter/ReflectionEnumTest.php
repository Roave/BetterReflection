<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection\Adapter;

use OutOfBoundsException;
use PHPUnit\Framework\TestCase;
use ReflectionClass as CoreReflectionClass;
use ReflectionEnum as CoreReflectionEnum;
use ReflectionException as CoreReflectionException;
use ReflectionProperty as CoreReflectionProperty;
use Roave\BetterReflection\Reflection\Adapter\Exception\NotImplemented;
use Roave\BetterReflection\Reflection\Adapter\ReflectionAttribute as ReflectionAttributeAdapter;
use Roave\BetterReflection\Reflection\Adapter\ReflectionEnum as ReflectionEnumAdapter;
use Roave\BetterReflection\Reflection\Adapter\ReflectionEnumBackedCase as ReflectionEnumBackedCaseAdapter;
use Roave\BetterReflection\Reflection\Adapter\ReflectionEnumUnitCase as ReflectionEnumUnitCaseAdapter;
use Roave\BetterReflection\Reflection\Adapter\ReflectionNamedType as ReflectionNamedTypeAdapter;
use Roave\BetterReflection\Reflection\ReflectionAttribute as BetterReflectionAttribute;
use Roave\BetterReflection\Reflection\ReflectionClass as BetterReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionClassConstant as BetterReflectionClassConstant;
use Roave\BetterReflection\Reflection\ReflectionEnum as BetterReflectionEnum;
use Roave\BetterReflection\Reflection\ReflectionEnumCase as BetterReflectionEnumCase;
use Roave\BetterReflection\Reflection\ReflectionMethod as BetterReflectionMethod;
use Roave\BetterReflection\Reflection\ReflectionNamedType as BetterReflectionNamedType;
use Roave\BetterReflection\Reflection\ReflectionProperty as BetterReflectionProperty;
use stdClass;

use function array_combine;
use function array_map;
use function get_class_methods;

/**
 * @covers \Roave\BetterReflection\Reflection\Adapter\ReflectionEnum
 */
class ReflectionEnumTest extends TestCase
{
    public function coreReflectionMethodNamesProvider(): array
    {
        $methods = get_class_methods(CoreReflectionEnum::class);

        return array_combine($methods, array_map(static fn (string $i): array => [$i], $methods));
    }

    /**
     * @dataProvider coreReflectionMethodNamesProvider
     */
    public function testCoreReflectionMethods(string $methodName): void
    {
        $reflectionEnumAdapterReflection = new CoreReflectionClass(ReflectionEnumAdapter::class);

        self::assertTrue($reflectionEnumAdapterReflection->hasMethod($methodName));
        self::assertSame(ReflectionEnumAdapter::class, $reflectionEnumAdapterReflection->getMethod($methodName)->getDeclaringClass()->getName());
    }

    public function methodExpectationProvider(): array
    {
        $mockMethod = $this->createMock(BetterReflectionMethod::class);

        $mockProperty = $this->createMock(BetterReflectionProperty::class);

        $mockInterfaceLike = $this->createMock(BetterReflectionClass::class);

        $mockConstant = $this->createMock(BetterReflectionClassConstant::class);

        $mockEnumCase = $this->createMock(BetterReflectionEnumCase::class);

        return [
            // Inherited
            ['__toString', null, '', []],
            ['getName', null, '', []],
            ['isAnonymous', null, true, []],
            ['isInternal', null, true, []],
            ['isUserDefined', null, true, []],
            ['isInstantiable', null, true, []],
            ['isCloneable', null, true, []],
            ['getFileName', null, '', []],
            ['getStartLine', null, 123, []],
            ['getEndLine', null, 123, []],
            ['getDocComment', null, '', []],
            ['getConstructor', null, $mockMethod, []],
            ['hasMethod', null, true, ['foo']],
            ['getMethod', null, $mockMethod, ['foo']],
            ['getMethods', null, [$mockMethod], []],
            ['hasProperty', null, true, ['foo']],
            ['getProperty', null, $mockProperty, ['foo']],
            ['getProperties', null, [$mockProperty], []],
            ['hasConstant', null, true, ['foo']],
            ['getConstant', null, 'a', ['foo']],
            ['getReflectionConstant', null, $mockConstant, ['foo']],
            ['getReflectionConstants', null, [$mockConstant], []],
            ['getInterfaces', null, [$mockInterfaceLike], []],
            ['getInterfaceNames', null, ['a', 'b'], []],
            ['isInterface', null, true, []],
            ['getTraits', null, [], []],
            ['getTraitNames', null, ['a', 'b'], []],
            ['getTraitAliases', null, ['a', 'b'], []],
            ['isTrait', null, true, []],
            ['isAbstract', null, true, []],
            ['isFinal', null, true, []],
            ['getModifiers', null, 123, []],
            ['isInstance', null, true, [new stdClass()]],
            ['newInstance', NotImplemented::class, null, []],
            ['newInstanceWithoutConstructor', NotImplemented::class, null, []],
            ['newInstanceArgs', NotImplemented::class, null, []],
            ['isSubclassOf', null, true, ['\stdClass']],
            ['getStaticProperties', null, [], []],
            ['getDefaultProperties', null, ['foo' => 'bar'], []],
            ['isIterateable', null, true, []],
            ['implementsInterface', null, true, ['\Traversable']],
            ['getExtension', NotImplemented::class, null, []],
            ['getExtensionName', null, null, []],
            ['inNamespace', null, true, []],
            ['getNamespaceName', null, '', []],
            ['getShortName', null, '', []],
            ['getAttributes', null, [], []],
            ['isEnum', null, true, []],

            // ReflectionEnum
            ['hasCase', null, false, ['case']],
            ['getCase', null, $mockEnumCase, ['case']],
            ['getCases', null, [], []],
            ['isBacked', null, false, []],
        ];
    }

    /**
     * @param mixed[] $args
     *
     * @dataProvider methodExpectationProvider
     */
    public function testAdapterMethods(string $methodName, ?string $expectedException, mixed $returnValue, array $args): void
    {
        $reflectionStub = $this->createMock(BetterReflectionEnum::class);

        if ($expectedException === null) {
            $reflectionStub->expects($this->once())
                ->method($methodName)
                ->with(...$args)
                ->will($this->returnValue($returnValue));
        }

        if ($expectedException !== null) {
            $this->expectException($expectedException);
        }

        $adapter = new ReflectionEnumAdapter($reflectionStub);
        $adapter->{$methodName}(...$args);
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
        $reflectionEnumAdapter->foo;
    }

    public function testGetConstructorReturnsNullWhenNoConstructorExists(): void
    {
        $betterReflectionEnum = $this->createMock(BetterReflectionEnum::class);
        $betterReflectionEnum
            ->method('getConstructor')
            ->willThrowException(new OutOfBoundsException());

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
            ->method('getModifiers')
            ->willReturn(CoreReflectionProperty::IS_PUBLIC);

        $publicBetterReflectionClassConstant
            ->method('getName')
            ->willReturn('PUBLIC_CONSTANT');

        $publicBetterReflectionClassConstant
            ->method('getValue')
            ->willReturn('public constant');

        $privateBetterReflectionClassConstant
            ->method('getModifiers')
            ->willReturn(CoreReflectionProperty::IS_PRIVATE);

        $privateBetterReflectionClassConstant
            ->method('getName')
            ->willReturn('PRIVATE_CONSTANT');

        $privateBetterReflectionClassConstant
            ->method('getValue')
            ->willReturn('private constant');

        $protectedBetterReflectionClassConstant
            ->method('getModifiers')
            ->willReturn(CoreReflectionProperty::IS_PROTECTED);

        $protectedBetterReflectionClassConstant
            ->method('getName')
            ->willReturn('PROTECTED_CONSTANT');

        $protectedBetterReflectionClassConstant
            ->method('getValue')
            ->willReturn('protected constant');

        $betterReflectionEnum
            ->method('getReflectionConstants')
            ->willReturn([
                $publicBetterReflectionClassConstant->getName() => $publicBetterReflectionClassConstant,
                $privateBetterReflectionClassConstant->getName() => $privateBetterReflectionClassConstant,
                $protectedBetterReflectionClassConstant->getName() => $protectedBetterReflectionClassConstant,
            ]);

        $reflectionEnumAdapter = new ReflectionEnumAdapter($betterReflectionEnum);

        $allConstants       = $reflectionEnumAdapter->getConstants();
        $publicConstants    = $reflectionEnumAdapter->getConstants(CoreReflectionProperty::IS_PUBLIC);
        $privateConstants   = $reflectionEnumAdapter->getConstants(CoreReflectionProperty::IS_PRIVATE);
        $protectedConstants = $reflectionEnumAdapter->getConstants(CoreReflectionProperty::IS_PROTECTED);

        self::assertCount(3, $allConstants);

        self::assertCount(1, $publicConstants);
        self::assertEquals([$publicBetterReflectionClassConstant->getName() => $publicBetterReflectionClassConstant->getValue()], $publicConstants);

        self::assertCount(1, $privateConstants);
        self::assertEquals([$privateBetterReflectionClassConstant->getName() => $privateBetterReflectionClassConstant->getValue()], $privateConstants);

        self::assertCount(1, $protectedConstants);
        self::assertEquals([$protectedBetterReflectionClassConstant->getName() => $protectedBetterReflectionClassConstant->getValue()], $protectedConstants);
    }

    public function testGetReflectionConstantReturnsFalseWhenConstantDoesNotExist(): void
    {
        $betterReflectionEnum = $this->createMock(BetterReflectionEnum::class);
        $betterReflectionEnum
            ->method('getReflectionConstant')
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
        $betterReflectionAttribute1 = $this->createMock(BetterReflectionAttribute::class);
        $betterReflectionAttribute1
            ->method('getName')
            ->willReturn('SomeAttribute');
        $betterReflectionAttribute2 = $this->createMock(BetterReflectionAttribute::class);
        $betterReflectionAttribute2
            ->method('getName')
            ->willReturn('AnotherAttribute');

        $betterReflectionAttributes = [$betterReflectionAttribute1, $betterReflectionAttribute2];

        $betterReflectionEnum = $this->getMockBuilder(BetterReflectionEnum::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAttributes'])
            ->getMock();

        $betterReflectionEnum
            ->method('getAttributes')
            ->willReturn($betterReflectionAttributes);

        $reflectionEnumAdapter = new ReflectionEnumAdapter($betterReflectionEnum);
        $attributes            = $reflectionEnumAdapter->getAttributes('SomeAttribute');

        self::assertCount(1, $attributes);
        self::assertSame('SomeAttribute', $attributes[0]->getName());
    }

    public function testGetAttributesWithInstance(): void
    {
        $betterReflectionAttributeClass1 = $this->createMock(BetterReflectionClass::class);
        $betterReflectionAttributeClass1
            ->method('getName')
            ->willReturn('ClassName');
        $betterReflectionAttributeClass1
            ->method('isSubclassOf')
            ->willReturnMap([
                ['ParentClassName', true],
                ['InterfaceName', false],
            ]);
        $betterReflectionAttributeClass1
            ->method('implementsInterface')
            ->willReturnMap([
                ['ParentClassName', false],
                ['InterfaceName', false],
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
                ['ClassName', false],
                ['ParentClassName', false],
                ['InterfaceName', false],
            ]);
        $betterReflectionAttributeClass2
            ->method('implementsInterface')
            ->willReturnMap([
                ['ClassName', false],
                ['ParentClassName', false],
                ['InterfaceName', true],
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
                ['ClassName', false],
                ['ParentClassName', true],
                ['InterfaceName', false],
            ]);
        $betterReflectionAttributeClass3
            ->method('implementsInterface')
            ->willReturnMap([
                ['ClassName', false],
                ['ParentClassName', false],
                ['InterfaceName', true],
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

        self::assertCount(1, $reflectionEnumAdapter->getAttributes('ClassName', ReflectionAttributeAdapter::IS_INSTANCEOF));
        self::assertCount(2, $reflectionEnumAdapter->getAttributes('ParentClassName', ReflectionAttributeAdapter::IS_INSTANCEOF));
        self::assertCount(2, $reflectionEnumAdapter->getAttributes('InterfaceName', ReflectionAttributeAdapter::IS_INSTANCEOF));
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

        self::assertInstanceOf(ReflectionNamedTypeAdapter::class, $reflectionEnumAdapter->getBackingType());
    }
}
