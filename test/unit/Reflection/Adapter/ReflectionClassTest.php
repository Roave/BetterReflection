<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection\Adapter;

use OutOfBoundsException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass as CoreReflectionClass;
use ReflectionException as CoreReflectionException;
use Roave\BetterReflection\Reflection\Adapter\Exception\NotImplemented;
use Roave\BetterReflection\Reflection\Adapter\ReflectionClass as ReflectionClassAdapter;
use Roave\BetterReflection\Reflection\ReflectionClass as BetterReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionClassConstant as BetterReflectionClassConstant;
use Roave\BetterReflection\Reflection\ReflectionMethod as BetterReflectionMethod;
use Roave\BetterReflection\Reflection\ReflectionProperty as BetterReflectionProperty;
use stdClass;
use function array_combine;
use function array_map;
use function get_class_methods;

/**
 * @covers \Roave\BetterReflection\Reflection\Adapter\ReflectionClass
 */
class ReflectionClassTest extends TestCase
{
    public function coreReflectionMethodNamesProvider() : array
    {
        $methods = get_class_methods(CoreReflectionClass::class);

        return array_combine($methods, array_map(static function (string $i) : array {
            return [$i];
        }, $methods));
    }

    /**
     * @dataProvider coreReflectionMethodNamesProvider
     */
    public function testCoreReflectionMethods(string $methodName) : void
    {
        $reflectionClassAdapterReflection = new CoreReflectionClass(ReflectionClassAdapter::class);
        self::assertTrue($reflectionClassAdapterReflection->hasMethod($methodName));
    }

    public function methodExpectationProvider() : array
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
            ['getConstants', null, ['a', 'b'], []],
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
        ];
    }

    /**
     * @param mixed   $returnValue
     * @param mixed[] $args
     *
     * @dataProvider methodExpectationProvider
     */
    public function testAdapterMethods(string $methodName, ?string $expectedException, $returnValue, array $args) : void
    {
        /** @var BetterReflectionClass|MockObject $reflectionStub */
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

    public function testExport() : void
    {
        $exported = ReflectionClassAdapter::export('\stdClass');

        self::assertIsString($exported);
        self::assertStringContainsString('stdClass', $exported);
    }

    public function testGetFileNameReturnsFalseWhenNoFileName() : void
    {
        $betterReflectionClass = $this->createMock(BetterReflectionClass::class);
        $betterReflectionClass
            ->method('getFileName')
            ->willReturn(null);

        $reflectionClassAdapter = new ReflectionClassAdapter($betterReflectionClass);

        self::assertFalse($reflectionClassAdapter->getFileName());
    }

    public function testGetDocCommentReturnsFalseWhenNoDocComment() : void
    {
        $betterReflectionClass = $this->createMock(BetterReflectionClass::class);
        $betterReflectionClass
            ->method('getDocComment')
            ->willReturn('');

        $reflectionClassAdapter = new ReflectionClassAdapter($betterReflectionClass);

        self::assertFalse($reflectionClassAdapter->getDocComment());
    }

    public function testGetParentClassReturnsFalseWhenNoParent() : void
    {
        $betterReflectionClass = $this->createMock(BetterReflectionClass::class);
        $betterReflectionClass
            ->method('getParentClass')
            ->willReturn(null);

        $reflectionClassAdapter = new ReflectionClassAdapter($betterReflectionClass);

        self::assertFalse($reflectionClassAdapter->getParentClass());
    }

    public function testHasMethodIsCaseInsensitive() : void
    {
        $betterReflectionMethod = $this->createMock(BetterReflectionMethod::class);
        $betterReflectionMethod
            ->method('getName')
            ->willReturn('foo');

        $betterReflectionClass = $this->createMock(BetterReflectionClass::class);
        $betterReflectionClass
            ->method('getMethods')
            ->willReturn([$betterReflectionMethod]);
        $betterReflectionClass
            ->method('hasMethod')
            ->with('foo')
            ->willReturn(true);

        $reflectionClassAdapter = new ReflectionClassAdapter($betterReflectionClass);

        self::assertTrue($reflectionClassAdapter->hasMethod('foo'));
        self::assertTrue($reflectionClassAdapter->hasMethod('FOO'));
    }

    public function testGetMethodIsCaseInsensitive() : void
    {
        $betterReflectionMethod = $this->createMock(BetterReflectionMethod::class);
        $betterReflectionMethod
            ->method('getName')
            ->willReturn('foo');

        $betterReflectionClass = $this->createMock(BetterReflectionClass::class);
        $betterReflectionClass
            ->method('getMethods')
            ->willReturn([$betterReflectionMethod]);
        $betterReflectionClass
            ->method('getMethod')
            ->with('foo')
            ->willReturn($betterReflectionMethod);

        $reflectionClassAdapter = new ReflectionClassAdapter($betterReflectionClass);

        self::assertSame('foo', $reflectionClassAdapter->getMethod('foo')->getName());
        self::assertSame('foo', $reflectionClassAdapter->getMethod('FOO')->getName());
    }

    public function testIsSubclassOfIsCaseInsensitive() : void
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

    public function testIsSubclassOfChecksAlsoImplementedInterfaces() : void
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

    public function testImplementsInterfaceIsCaseInsensitive() : void
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

    public function testGetPropertyThrowsExceptionWhenPropertyDoesNotExist() : void
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

    public function testGetStaticPropertyValue() : void
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

    public function testSetStaticPropertyValue() : void
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

    public function testGetStaticPropertyValueThrowsExceptionWhenPropertyNotAccessible() : void
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

    public function testSetStaticPropertyValueThrowsExceptionWhenPropertyNotAccessible() : void
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

    public function testGetStaticPropertyValueThrowsExceptionWhenPropertyDoesNotExist() : void
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

    public function testGetStaticPropertyValueReturnsDefaultValueWhenPropertyDoesNotExist() : void
    {
        $betterReflectionClass = $this->createMock(BetterReflectionClass::class);
        $betterReflectionClass
            ->method('getProperty')
            ->with('foo')
            ->willReturn(null);

        $reflectionClassAdapter = new ReflectionClassAdapter($betterReflectionClass);

        self::assertSame('default', $reflectionClassAdapter->getStaticPropertyValue('foo', 'default'));
    }

    public function testSetStaticPropertyValueThrowsExceptionWhenPropertyDoesNotExist() : void
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

    public function testGetExtensionNameReturnsFalseWhenNoExtensionName() : void
    {
        $betterReflectionClass = $this->createMock(BetterReflectionClass::class);
        $betterReflectionClass
            ->method('getExtensionName')
            ->willReturn(null);

        $reflectionClassAdapter = new ReflectionClassAdapter($betterReflectionClass);

        self::assertFalse($reflectionClassAdapter->getExtensionName());
    }

    public function testGetConstructorReturnsNullWhenNoConstructorExists() : void
    {
        $betterReflectionClass = $this->createMock(BetterReflectionClass::class);
        $betterReflectionClass
            ->method('getConstructor')
            ->willThrowException(new OutOfBoundsException());

        $reflectionClassAdapter = new ReflectionClassAdapter($betterReflectionClass);

        self::assertNull($reflectionClassAdapter->getConstructor());
    }
}
