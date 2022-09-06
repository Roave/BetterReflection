<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection\Adapter;

use OutOfBoundsException;
use PHPUnit\Framework\TestCase;
use ReflectionClass as CoreReflectionClass;
use ReflectionException as CoreReflectionException;
use ReflectionMethod as CoreReflectionMethod;
use ReflectionProperty as CoreReflectionProperty;
use Roave\BetterReflection\Reflection\Adapter\Exception\NotImplemented;
use Roave\BetterReflection\Reflection\Adapter\ReflectionAttribute as ReflectionAttributeAdapter;
use Roave\BetterReflection\Reflection\Adapter\ReflectionClass as ReflectionClassAdapter;
use Roave\BetterReflection\Reflection\Adapter\ReflectionClassConstant as ReflectionClassConstantAdapter;
use Roave\BetterReflection\Reflection\Adapter\ReflectionMethod as ReflectionMethodAdapter;
use Roave\BetterReflection\Reflection\Adapter\ReflectionProperty as ReflectionPropertyAdapter;
use Roave\BetterReflection\Reflection\ReflectionAttribute as BetterReflectionAttribute;
use Roave\BetterReflection\Reflection\ReflectionClass as BetterReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionClassConstant as BetterReflectionClassConstant;
use Roave\BetterReflection\Reflection\ReflectionEnum as BetterReflectionEnum;
use Roave\BetterReflection\Reflection\ReflectionEnumCase as BetterReflectionEnumCase;
use Roave\BetterReflection\Reflection\ReflectionMethod as BetterReflectionMethod;
use Roave\BetterReflection\Reflection\ReflectionProperty as BetterReflectionProperty;
use Roave\BetterReflection\Util\FileHelper;
use Roave\BetterReflectionTest\Fixture\AutoloadableEnum;
use stdClass;
use ValueError;

use function array_combine;
use function array_map;
use function get_class_methods;
use function is_array;

/** @covers \Roave\BetterReflection\Reflection\Adapter\ReflectionClass */
class ReflectionClassTest extends TestCase
{
    /** @return array<string, array{0: string}> */
    public function coreReflectionMethodNamesProvider(): array
    {
        $methods = get_class_methods(CoreReflectionClass::class);

        return array_combine($methods, array_map(static fn (string $i): array => [$i], $methods));
    }

    /** @dataProvider coreReflectionMethodNamesProvider */
    public function testCoreReflectionMethods(string $methodName): void
    {
        $reflectionClassAdapterReflection = new CoreReflectionClass(ReflectionClassAdapter::class);

        self::assertTrue($reflectionClassAdapterReflection->hasMethod($methodName));
        self::assertSame(ReflectionClassAdapter::class, $reflectionClassAdapterReflection->getMethod($methodName)->getDeclaringClass()->getName());
    }

    /** @return list<array{0: string, 1: list<mixed>, 2: mixed, 3: string|null, 4: mixed, 5: string|null}> */
    public function methodExpectationProvider(): array
    {
        $mockMethod = $this->createMock(BetterReflectionMethod::class);

        $mockProperty = $this->createMock(BetterReflectionProperty::class);

        $mockClassLike = $this->createMock(BetterReflectionClass::class);

        $mockConstant = $this->createMock(BetterReflectionClassConstant::class);

        $mockAttribute = $this->createMock(BetterReflectionAttribute::class);

        return [
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
            ['getDocComment', [], '', null, false, null],
            ['getConstructor', [], $mockMethod, null, null, ReflectionMethodAdapter::class],
            ['hasMethod', ['foo'], true, null, true, null],
            ['getMethod', ['foo'], $mockMethod, null, null, ReflectionMethodAdapter::class],
            ['getMethods', [], [$mockMethod], null, null, ReflectionMethodAdapter::class],
            ['hasProperty', ['foo'], true, null, true, null],
            ['getProperty', ['foo'], $mockProperty, null, null, ReflectionPropertyAdapter::class],
            ['getProperties', [], [$mockProperty], null, null, ReflectionPropertyAdapter::class],
            ['hasConstant', ['foo'], true, null, true, null],
            ['getConstant', ['foo'], 'a', null, 'a', null],
            ['getReflectionConstant', ['foo'], $mockConstant, null, null, ReflectionClassConstantAdapter::class],
            ['getReflectionConstants', [], [$mockConstant], null, null, ReflectionClassConstantAdapter::class],
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
            ['getParentClass', [], $mockClassLike, null, null, ReflectionClassAdapter::class],
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
        $reflectionStub = $this->createMock(BetterReflectionClass::class);

        if ($expectedException === null) {
            $reflectionStub->expects($this->once())
                ->method($methodName)
                ->with(...$args)
                ->willReturn($returnValue);
        }

        $adapter = new ReflectionClassAdapter($reflectionStub);

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

    public function testGetFileNameReturnsFalseWhenNoFileName(): void
    {
        $betterReflectionClass = $this->createMock(BetterReflectionClass::class);
        $betterReflectionClass
            ->method('getFileName')
            ->willReturn(null);

        $reflectionClassAdapter = new ReflectionClassAdapter($betterReflectionClass);

        self::assertFalse($reflectionClassAdapter->getFileName());
    }

    public function testGetFileNameReturnsPathWithSystemDirectorySeparator(): void
    {
        $fileName = 'foo/bar\\foo/bar.php';

        $betterReflectionClass = $this->createMock(BetterReflectionClass::class);
        $betterReflectionClass
            ->method('getFileName')
            ->willReturn($fileName);

        $reflectionClassAdapter = new ReflectionClassAdapter($betterReflectionClass);

        self::assertSame(FileHelper::normalizeSystemPath($fileName), $reflectionClassAdapter->getFileName());
    }

    public function testGetDocCommentReturnsFalseWhenNoDocComment(): void
    {
        $betterReflectionClass = $this->createMock(BetterReflectionClass::class);
        $betterReflectionClass
            ->method('getDocComment')
            ->willReturn('');

        $reflectionClassAdapter = new ReflectionClassAdapter($betterReflectionClass);

        self::assertFalse($reflectionClassAdapter->getDocComment());
    }

    public function testGetParentClassReturnsFalseWhenNoParent(): void
    {
        $betterReflectionClass = $this->createMock(BetterReflectionClass::class);
        $betterReflectionClass
            ->method('getParentClass')
            ->willReturn(null);

        $reflectionClassAdapter = new ReflectionClassAdapter($betterReflectionClass);

        self::assertFalse($reflectionClassAdapter->getParentClass());
    }

    public function testGetMethodsFilter(): void
    {
        $publicBetterReflectionMethod = $this->createMock(BetterReflectionMethod::class);
        $publicBetterReflectionMethod
            ->method('getName')
            ->willReturn('publicMethod');

        $privateBetterReflectionMethod = $this->createMock(BetterReflectionMethod::class);
        $privateBetterReflectionMethod
            ->method('getName')
            ->willReturn('privateMethod');

        $betterReflectionClass = $this->createMock(BetterReflectionClass::class);
        $betterReflectionClass
            ->method('getMethods')
            ->willReturnMap([
                [null, [$publicBetterReflectionMethod, $privateBetterReflectionMethod]],
                [CoreReflectionMethod::IS_PUBLIC, [$publicBetterReflectionMethod]],
                [CoreReflectionMethod::IS_PRIVATE, [$privateBetterReflectionMethod]],
            ]);

        $reflectionClassAdapter = new ReflectionClassAdapter($betterReflectionClass);

        self::assertCount(2, $reflectionClassAdapter->getMethods());

        $publicMethods = $reflectionClassAdapter->getMethods(CoreReflectionMethod::IS_PUBLIC);

        self::assertCount(1, $publicMethods);
        self::assertSame($publicBetterReflectionMethod->getName(), $publicMethods[0]->getName());

        $privateMethods = $reflectionClassAdapter->getMethods(CoreReflectionMethod::IS_PRIVATE);

        self::assertCount(1, $privateMethods);
        self::assertSame($privateBetterReflectionMethod->getName(), $privateMethods[0]->getName());
    }

    public function testGetReflectionConstantsWithFilter(): void
    {
        $betterReflectionClass                  = $this->createMock(BetterReflectionClass::class);
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

        $betterReflectionClass
            ->method('getReflectionConstants')
            ->willReturn([
                $publicBetterReflectionClassConstant->getName() => $publicBetterReflectionClassConstant,
                $privateBetterReflectionClassConstant->getName() => $privateBetterReflectionClassConstant,
                $protectedBetterReflectionClassConstant->getName() => $protectedBetterReflectionClassConstant,
            ]);

        $reflectionClassAdapter = new ReflectionClassAdapter($betterReflectionClass);

        $allConstants       = $reflectionClassAdapter->getReflectionConstants();
        $publicConstants    = $reflectionClassAdapter->getReflectionConstants(CoreReflectionProperty::IS_PUBLIC);
        $privateConstants   = $reflectionClassAdapter->getReflectionConstants(CoreReflectionProperty::IS_PRIVATE);
        $protectedConstants = $reflectionClassAdapter->getReflectionConstants(CoreReflectionProperty::IS_PROTECTED);

        self::assertCount(3, $allConstants);
        self::assertContainsOnlyInstancesOf(ReflectionClassConstantAdapter::class, $allConstants);

        self::assertCount(1, $publicConstants);
        self::assertSame($publicBetterReflectionClassConstant->getName(), $publicConstants[0]->getName());

        self::assertCount(1, $privateConstants);
        self::assertSame($privateBetterReflectionClassConstant->getName(), $privateConstants[0]->getName());

        self::assertCount(1, $protectedConstants);
        self::assertSame($protectedBetterReflectionClassConstant->getName(), $protectedConstants[0]->getName());
    }

    public function testGetConstantsWithFilter(): void
    {
        $betterReflectionClass                  = $this->createMock(BetterReflectionClass::class);
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

        $betterReflectionClass
            ->method('getReflectionConstants')
            ->willReturn([
                $publicBetterReflectionClassConstant->getName() => $publicBetterReflectionClassConstant,
                $privateBetterReflectionClassConstant->getName() => $privateBetterReflectionClassConstant,
                $protectedBetterReflectionClassConstant->getName() => $protectedBetterReflectionClassConstant,
            ]);

        $reflectionClassAdapter = new ReflectionClassAdapter($betterReflectionClass);

        $allConstants       = $reflectionClassAdapter->getConstants();
        $publicConstants    = $reflectionClassAdapter->getConstants(CoreReflectionProperty::IS_PUBLIC);
        $privateConstants   = $reflectionClassAdapter->getConstants(CoreReflectionProperty::IS_PRIVATE);
        $protectedConstants = $reflectionClassAdapter->getConstants(CoreReflectionProperty::IS_PROTECTED);

        self::assertCount(3, $allConstants);

        self::assertCount(1, $publicConstants);
        self::assertEquals([$publicBetterReflectionClassConstant->getName() => $publicBetterReflectionClassConstant->getValue()], $publicConstants);

        self::assertCount(1, $privateConstants);
        self::assertEquals([$privateBetterReflectionClassConstant->getName() => $privateBetterReflectionClassConstant->getValue()], $privateConstants);

        self::assertCount(1, $protectedConstants);
        self::assertEquals([$protectedBetterReflectionClassConstant->getName() => $protectedBetterReflectionClassConstant->getValue()], $protectedConstants);
    }

    public function testIsSubclassOfWithObject(): void
    {
        $betterReflectionClass = $this->createMock(BetterReflectionClass::class);
        $betterReflectionClass
            ->method('getParentClassNames')
            ->willReturn(['Foo']);
        $betterReflectionClass
            ->method('isSubclassOf')
            ->with('Foo')
            ->willReturn(true);

        $reflectionClassAdapter = new ReflectionClassAdapter($betterReflectionClass);

        $fooClassMock = $this->createMock(CoreReflectionClass::class);
        $fooClassMock
            ->method('getName')
            ->willReturn('Foo');

        self::assertTrue($reflectionClassAdapter->isSubclassOf($fooClassMock));
    }

    public function testIsSubclassOfIsCaseInsensitive(): void
    {
        $betterReflectionClass = $this->createMock(BetterReflectionClass::class);
        $betterReflectionClass
            ->method('getParentClassNames')
            ->willReturn(['Foo']);
        $betterReflectionClass
            ->method('isSubclassOf')
            ->with('Foo')
            ->willReturn(true);

        $reflectionClassAdapter = new ReflectionClassAdapter($betterReflectionClass);

        self::assertTrue($reflectionClassAdapter->isSubclassOf('Foo'));
        self::assertTrue($reflectionClassAdapter->isSubclassOf('foo'));
        self::assertTrue($reflectionClassAdapter->isSubclassOf('FoO'));
    }

    public function testIsSubclassOfChecksAlsoImplementedInterfaces(): void
    {
        $betterReflectionClass = $this->createMock(BetterReflectionClass::class);
        $betterReflectionClass
            ->method('getParentClassNames')
            ->willReturn([]);
        $betterReflectionClass
            ->method('isSubclassOf')
            ->with('Foo')
            ->willReturn(false);
        $betterReflectionClass
            ->method('getInterfaceNames')
            ->willReturn(['Foo']);
        $betterReflectionClass
            ->method('implementsInterface')
            ->with('Foo')
            ->willReturn(true);

        $reflectionClassAdapter = new ReflectionClassAdapter($betterReflectionClass);

        self::assertTrue($reflectionClassAdapter->isSubclassOf('Foo'));
    }

    public function testImplementsInterfaceWithObject(): void
    {
        $betterReflectionClass = $this->createMock(BetterReflectionClass::class);
        $betterReflectionClass
            ->method('getInterfaceNames')
            ->willReturn(['Foo']);
        $betterReflectionClass
            ->method('implementsInterface')
            ->with('Foo')
            ->willReturn(true);

        $reflectionClassAdapter = new ReflectionClassAdapter($betterReflectionClass);

        $fooClassMock = $this->createMock(CoreReflectionClass::class);
        $fooClassMock
            ->method('getName')
            ->willReturn('Foo');

        self::assertTrue($reflectionClassAdapter->implementsInterface($fooClassMock));
    }

    public function testImplementsInterfaceIsCaseInsensitive(): void
    {
        $betterReflectionClass = $this->createMock(BetterReflectionClass::class);
        $betterReflectionClass
            ->method('getInterfaceNames')
            ->willReturn(['Foo']);
        $betterReflectionClass
            ->method('implementsInterface')
            ->with('Foo')
            ->willReturn(true);

        $reflectionClassAdapter = new ReflectionClassAdapter($betterReflectionClass);

        self::assertTrue($reflectionClassAdapter->implementsInterface('Foo'));
        self::assertTrue($reflectionClassAdapter->implementsInterface('foo'));
        self::assertTrue($reflectionClassAdapter->implementsInterface('FoO'));
    }

    public function testGetPropertyThrowsExceptionWhenPropertyDoesNotExist(): void
    {
        $betterReflectionClass = $this->createMock(BetterReflectionClass::class);
        $betterReflectionClass
            ->method('getProperty')
            ->with('foo')
            ->willReturn(null);

        $reflectionClassAdapter = new ReflectionClassAdapter($betterReflectionClass);

        $this->expectException(CoreReflectionException::class);
        $reflectionClassAdapter->getProperty('foo');
    }

    public function testGetPropertiesFilter(): void
    {
        $publicBetterReflectionProperty = $this->createMock(BetterReflectionProperty::class);
        $publicBetterReflectionProperty
            ->method('getName')
            ->willReturn('publicProperty');

        $privateBetterReflectionProperty = $this->createMock(BetterReflectionProperty::class);
        $privateBetterReflectionProperty
            ->method('getName')
            ->willReturn('privateProperty');

        $betterReflectionClass = $this->createMock(BetterReflectionClass::class);
        $betterReflectionClass
            ->method('getProperties')
            ->willReturnMap([
                [null, [$publicBetterReflectionProperty, $privateBetterReflectionProperty]],
                [CoreReflectionProperty::IS_PUBLIC, [$publicBetterReflectionProperty]],
                [CoreReflectionProperty::IS_PRIVATE, [$privateBetterReflectionProperty]],
            ]);

        $reflectionClassAdapter = new ReflectionClassAdapter($betterReflectionClass);

        self::assertCount(2, $reflectionClassAdapter->getProperties());

        $publicProperties = $reflectionClassAdapter->getProperties(CoreReflectionProperty::IS_PUBLIC);

        self::assertCount(1, $publicProperties);
        self::assertSame($publicBetterReflectionProperty->getName(), $publicProperties[0]->getName());

        $privateProperties = $reflectionClassAdapter->getProperties(CoreReflectionProperty::IS_PRIVATE);

        self::assertCount(1, $privateProperties);
        self::assertSame($privateBetterReflectionProperty->getName(), $privateProperties[0]->getName());
    }

    public function testGetStaticPropertyValue(): void
    {
        $betterReflectionProperty = $this->createMock(BetterReflectionProperty::class);
        $betterReflectionProperty
            ->method('isPublic')
            ->willReturn(true);
        $betterReflectionProperty
            ->method('isStatic')
            ->willReturn(true);
        $betterReflectionProperty
            ->method('getValue')
            ->willReturn(123);

        $betterReflectionClass = $this->createMock(BetterReflectionClass::class);
        $betterReflectionClass
            ->method('getProperty')
            ->with('foo')
            ->willReturn($betterReflectionProperty);

        $reflectionClassAdapter = new ReflectionClassAdapter($betterReflectionClass);

        self::assertSame(123, $reflectionClassAdapter->getStaticPropertyValue('foo'));
    }

    public function testSetStaticPropertyValue(): void
    {
        $betterReflectionProperty = $this->createMock(BetterReflectionProperty::class);
        $betterReflectionProperty
            ->method('isPublic')
            ->willReturn(true);
        $betterReflectionProperty
            ->method('isStatic')
            ->willReturn(true);
        $betterReflectionProperty
            ->expects($this->once())
            ->method('setValue')
            ->with(123);

        $betterReflectionClass = $this->createMock(BetterReflectionClass::class);
        $betterReflectionClass
            ->method('getProperty')
            ->with('foo')
            ->willReturn($betterReflectionProperty);

        $reflectionClassAdapter = new ReflectionClassAdapter($betterReflectionClass);

        $reflectionClassAdapter->setStaticPropertyValue('foo', 123);
    }

    public function testGetStaticPropertyValueThrowsExceptionWhenPropertyDoesNotExist(): void
    {
        $betterReflectionClass = $this->createMock(BetterReflectionClass::class);
        $betterReflectionClass
            ->method('getProperty')
            ->with('foo')
            ->willReturn(null);

        $reflectionClassAdapter = new ReflectionClassAdapter($betterReflectionClass);

        $this->expectException(CoreReflectionException::class);
        $this->expectExceptionMessage('Property "foo" does not exist');
        $reflectionClassAdapter->getStaticPropertyValue('foo');
    }

    public function testGetStaticPropertyValueReturnsDefaultValueWhenPropertyDoesNotExist(): void
    {
        $betterReflectionClass = $this->createMock(BetterReflectionClass::class);
        $betterReflectionClass
            ->method('getProperty')
            ->with('foo')
            ->willReturn(null);

        $reflectionClassAdapter = new ReflectionClassAdapter($betterReflectionClass);

        self::assertSame('default', $reflectionClassAdapter->getStaticPropertyValue('foo', 'default'));
    }

    public function testSetStaticPropertyValueThrowsExceptionWhenPropertyDoesNotExist(): void
    {
        $betterReflectionClass = $this->createMock(BetterReflectionClass::class);
        $betterReflectionClass
            ->method('getProperty')
            ->with('foo')
            ->willReturn(null);

        $reflectionClassAdapter = new ReflectionClassAdapter($betterReflectionClass);

        $this->expectException(CoreReflectionException::class);
        $this->expectExceptionMessage('Property "foo" does not exist');
        $reflectionClassAdapter->setStaticPropertyValue('foo', null);
    }

    public function testGetStaticPropertyValueThrowsExceptionWhenPropertyIsNotStatic(): void
    {
        $betterReflectionProperty = $this->createMock(BetterReflectionProperty::class);
        $betterReflectionProperty
            ->method('isPublic')
            ->willReturn(true);
        $betterReflectionProperty
            ->method('isStatic')
            ->willReturn(false);

        $betterReflectionClass = $this->createMock(BetterReflectionClass::class);
        $betterReflectionClass
            ->method('getProperty')
            ->with('foo')
            ->willReturn($betterReflectionProperty);

        $reflectionClassAdapter = new ReflectionClassAdapter($betterReflectionClass);

        $this->expectException(CoreReflectionException::class);
        $this->expectExceptionMessage('Property "foo" is not static');
        $reflectionClassAdapter->getStaticPropertyValue('foo');
    }

    public function testSetStaticPropertyValueThrowsExceptionWhenPropertyIsNotStatic(): void
    {
        $betterReflectionProperty = $this->createMock(BetterReflectionProperty::class);
        $betterReflectionProperty
            ->method('isPublic')
            ->willReturn(true);
        $betterReflectionProperty
            ->method('isStatic')
            ->willReturn(false);

        $betterReflectionClass = $this->createMock(BetterReflectionClass::class);
        $betterReflectionClass
            ->method('getProperty')
            ->with('foo')
            ->willReturn($betterReflectionProperty);

        $reflectionClassAdapter = new ReflectionClassAdapter($betterReflectionClass);

        $this->expectException(CoreReflectionException::class);
        $this->expectExceptionMessage('Property "foo" is not static');
        $reflectionClassAdapter->setStaticPropertyValue('foo', null);
    }

    public function testIsIterable(): void
    {
        $betterReflectionClass = $this->createMock(BetterReflectionClass::class);
        $betterReflectionClass
            ->method('isIterateable')
            ->willReturn(true);

        $reflectionClassAdapter = new ReflectionClassAdapter($betterReflectionClass);

        self::assertTrue($reflectionClassAdapter->isIterable());
    }

    public function testGetExtensionNameReturnsEmptyStringWhenNoExtensionName(): void
    {
        $betterReflectionClass = $this->createMock(BetterReflectionClass::class);
        $betterReflectionClass
            ->method('getExtensionName')
            ->willReturn('');

        $reflectionClassAdapter = new ReflectionClassAdapter($betterReflectionClass);

        self::assertSame('', $reflectionClassAdapter->getExtensionName());
    }

    public function testGetConstructorReturnsNullWhenNoConstructorExists(): void
    {
        $betterReflectionClass = $this->createMock(BetterReflectionClass::class);
        $betterReflectionClass
            ->method('getConstructor')
            ->willThrowException(new OutOfBoundsException());

        $reflectionClassAdapter = new ReflectionClassAdapter($betterReflectionClass);

        self::assertNull($reflectionClassAdapter->getConstructor());
    }

    public function testGetReflectionConstantReturnsFalseWhenConstantDoesNotExist(): void
    {
        $betterReflectionClass = $this->createMock(BetterReflectionClass::class);
        $betterReflectionClass
            ->method('getReflectionConstant')
            ->with('FOO')
            ->willReturn(null);

        $reflectionClassAdapter = new ReflectionClassAdapter($betterReflectionClass);

        self::assertFalse($reflectionClassAdapter->getReflectionConstant('FOO'));
    }

    public function testPropertyName(): void
    {
        $betterReflectionClass = $this->createMock(BetterReflectionClass::class);
        $betterReflectionClass
            ->method('getName')
            ->willReturn('Foo');

        $reflectionClassAdapter = new ReflectionClassAdapter($betterReflectionClass);
        self::assertSame('Foo', $reflectionClassAdapter->name);
    }

    public function testUnknownProperty(): void
    {
        $betterReflectionClass  = $this->createMock(BetterReflectionClass::class);
        $reflectionClassAdapter = new ReflectionClassAdapter($betterReflectionClass);
        $this->expectException(OutOfBoundsException::class);
        $this->expectExceptionMessage('Property Roave\BetterReflection\Reflection\Adapter\ReflectionClass::$foo does not exist.');
        /** @phpstan-ignore-next-line */
        $reflectionClassAdapter->foo;
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

        $betterReflectionClass = $this->createMock(BetterReflectionClass::class);
        $betterReflectionClass
            ->method('getAttributes')
            ->willReturn($betterReflectionAttributes);

        $reflectionClassAdapter = new ReflectionClassAdapter($betterReflectionClass);
        $attributes             = $reflectionClassAdapter->getAttributes();

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

        $betterReflectionClass = $this->getMockBuilder(BetterReflectionClass::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAttributes'])
            ->getMock();

        $betterReflectionClass
            ->method('getAttributes')
            ->willReturn($betterReflectionAttributes);

        $reflectionClassAdapter = new ReflectionClassAdapter($betterReflectionClass);
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

        $betterReflectionClass = $this->getMockBuilder(BetterReflectionClass::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAttributes'])
            ->getMock();

        $betterReflectionClass
            ->method('getAttributes')
            ->willReturn($betterReflectionAttributes);

        $reflectionClassAdapter = new ReflectionClassAdapter($betterReflectionClass);

        self::assertCount(1, $reflectionClassAdapter->getAttributes($className, ReflectionAttributeAdapter::IS_INSTANCEOF));
        self::assertCount(2, $reflectionClassAdapter->getAttributes($parentClassName, ReflectionAttributeAdapter::IS_INSTANCEOF));
        self::assertCount(2, $reflectionClassAdapter->getAttributes($interfaceName, ReflectionAttributeAdapter::IS_INSTANCEOF));
    }

    public function testGetAttributesThrowsExceptionForInvalidFlags(): void
    {
        $betterReflectionClass  = $this->createMock(BetterReflectionClass::class);
        $reflectionClassAdapter = new ReflectionClassAdapter($betterReflectionClass);

        self::expectException(ValueError::class);
        $reflectionClassAdapter->getAttributes(null, 123);
    }

    public function testHasConstantWithEnumCase(): void
    {
        $betterReflectionEnum = $this->createMock(BetterReflectionEnum::class);
        $betterReflectionEnum
            ->method('hasCase')
            ->with('ENUM_CASE')
            ->willReturn(true);

        $reflectionClassAdapter = new ReflectionClassAdapter($betterReflectionEnum);

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

        $reflectionClassAdapter = new ReflectionClassAdapter($betterReflectionEnum);

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

        $reflectionClassAdapter = new ReflectionClassAdapter($betterReflectionEnum);

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
            ->method('getModifiers')
            ->willReturn(CoreReflectionProperty::IS_PUBLIC);

        $privateBetterReflectionClassConstant
            ->method('getModifiers')
            ->willReturn(CoreReflectionProperty::IS_PRIVATE);

        $protectedBetterReflectionClassConstant
            ->method('getModifiers')
            ->willReturn(CoreReflectionProperty::IS_PROTECTED);

        $betterReflectionEnum
            ->method('getCases')
            ->willReturn(['enum_case' => $betterReflectionEnumCase]);

        $betterReflectionEnum
            ->method('getReflectionConstants')
            ->willReturn([
                'public' => $publicBetterReflectionClassConstant,
                'private' => $privateBetterReflectionClassConstant,
                'protected' => $protectedBetterReflectionClassConstant,
            ]);

        $reflectionClassAdapter = new ReflectionClassAdapter($betterReflectionEnum);

        self::assertCount(4, $reflectionClassAdapter->getReflectionConstants());
        self::assertCount(2, $reflectionClassAdapter->getReflectionConstants(CoreReflectionProperty::IS_PUBLIC));
        self::assertCount(1, $reflectionClassAdapter->getReflectionConstants(CoreReflectionProperty::IS_PRIVATE));
        self::assertCount(1, $reflectionClassAdapter->getReflectionConstants(CoreReflectionProperty::IS_PROTECTED));
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

        $betterReflectionClass = $this->createMock(BetterReflectionClass::class);
        $betterReflectionClass
            ->method('getTraits')
            ->willReturn([$betterReflectionTrait1, $betterReflectionTrait2]);

        $reflectionClassAdapter = new ReflectionClassAdapter($betterReflectionClass);

        $traits = $reflectionClassAdapter->getTraits();

        self::assertContainsOnlyInstancesOf(ReflectionClassAdapter::class, $traits);
        self::assertCount(2, $traits);
        self::assertArrayHasKey($traitOneClassName, $traits);
        self::assertArrayHasKey($traitTwoClassName, $traits);
    }
}
