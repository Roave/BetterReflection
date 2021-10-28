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

use function array_combine;
use function array_map;
use function get_class_methods;

/**
 * @covers \Roave\BetterReflection\Reflection\Adapter\ReflectionClass
 */
class ReflectionClassTest extends TestCase
{
    public function coreReflectionMethodNamesProvider(): array
    {
        $methods = get_class_methods(CoreReflectionClass::class);

        return array_combine($methods, array_map(static fn (string $i): array => [$i], $methods));
    }

    /**
     * @dataProvider coreReflectionMethodNamesProvider
     */
    public function testCoreReflectionMethods(string $methodName): void
    {
        $reflectionClassAdapterReflection = new CoreReflectionClass(ReflectionClassAdapter::class);

        self::assertTrue($reflectionClassAdapterReflection->hasMethod($methodName));
        self::assertSame(ReflectionClassAdapter::class, $reflectionClassAdapterReflection->getMethod($methodName)->getDeclaringClass()->getName());
    }

    public function methodExpectationProvider(): array
    {
        $mockMethod = $this->createMock(BetterReflectionMethod::class);

        $mockProperty = $this->createMock(BetterReflectionProperty::class);

        $mockClassLike = $this->createMock(BetterReflectionClass::class);

        $mockConstant = $this->createMock(BetterReflectionClassConstant::class);

        return [
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
            ['getInterfaces', null, [$mockClassLike], []],
            ['getInterfaceNames', null, ['a', 'b'], []],
            ['isInterface', null, true, []],
            ['getTraits', null, [$mockClassLike], []],
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
            ['getParentClass', null, $mockClassLike, []],
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
        ];
    }

    /**
     * @param list<mixed> $args
     *
     * @dataProvider methodExpectationProvider
     */
    public function testAdapterMethods(string $methodName, ?string $expectedException, mixed $returnValue, array $args): void
    {
        $reflectionStub = $this->createMock(BetterReflectionClass::class);

        if ($expectedException === null) {
            $reflectionStub->expects($this->once())
                ->method($methodName)
                ->with(...$args)
                ->will($this->returnValue($returnValue));
        }

        if ($expectedException !== null) {
            $this->expectException($expectedException);
        }

        $adapter = new ReflectionClassAdapter($reflectionStub);
        $adapter->{$methodName}(...$args);
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

    public function testGetStaticPropertyValueThrowsExceptionWhenPropertyNotAccessible(): void
    {
        $betterReflectionProperty = $this->createMock(BetterReflectionProperty::class);
        $betterReflectionProperty
            ->method('isPublic')
            ->willReturn(false);

        $betterReflectionClass = $this->createMock(BetterReflectionClass::class);
        $betterReflectionClass
            ->method('getProperty')
            ->with('foo')
            ->willReturn($betterReflectionProperty);

        $reflectionClassAdapter = new ReflectionClassAdapter($betterReflectionClass);

        $this->expectException(CoreReflectionException::class);
        $reflectionClassAdapter->getStaticPropertyValue('foo');
    }

    public function testSetStaticPropertyValueThrowsExceptionWhenPropertyNotAccessible(): void
    {
        $betterReflectionProperty = $this->createMock(BetterReflectionProperty::class);
        $betterReflectionProperty
            ->method('isPublic')
            ->willReturn(false);

        $betterReflectionClass = $this->createMock(BetterReflectionClass::class);
        $betterReflectionClass
            ->method('getProperty')
            ->with('foo')
            ->willReturn($betterReflectionProperty);

        $reflectionClassAdapter = new ReflectionClassAdapter($betterReflectionClass);

        $this->expectException(CoreReflectionException::class);
        $reflectionClassAdapter->setStaticPropertyValue('foo', null);
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
        $betterReflectionAttribute1 = $this->createMock(BetterReflectionAttribute::class);
        $betterReflectionAttribute1
            ->method('getName')
            ->willReturn('SomeAttribute');
        $betterReflectionAttribute2 = $this->createMock(BetterReflectionAttribute::class);
        $betterReflectionAttribute2
            ->method('getName')
            ->willReturn('AnotherAttribute');

        $betterReflectionAttributes = [$betterReflectionAttribute1, $betterReflectionAttribute2];

        $betterReflectionClass = $this->getMockBuilder(BetterReflectionClass::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAttributes'])
            ->getMock();

        $betterReflectionClass
            ->method('getAttributes')
            ->willReturn($betterReflectionAttributes);

        $reflectionClassAdapter = new ReflectionClassAdapter($betterReflectionClass);
        $attributes             = $reflectionClassAdapter->getAttributes('SomeAttribute');

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

        $betterReflectionClass = $this->getMockBuilder(BetterReflectionClass::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAttributes'])
            ->getMock();

        $betterReflectionClass
            ->method('getAttributes')
            ->willReturn($betterReflectionAttributes);

        $reflectionClassAdapter = new ReflectionClassAdapter($betterReflectionClass);

        self::assertCount(1, $reflectionClassAdapter->getAttributes('ClassName', ReflectionAttributeAdapter::IS_INSTANCEOF));
        self::assertCount(2, $reflectionClassAdapter->getAttributes('ParentClassName', ReflectionAttributeAdapter::IS_INSTANCEOF));
        self::assertCount(2, $reflectionClassAdapter->getAttributes('InterfaceName', ReflectionAttributeAdapter::IS_INSTANCEOF));
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
}
