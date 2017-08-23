<?php
declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection\Adapter;

use Roave\BetterReflection\Reflection\Adapter\Exception\NotImplemented;
use ReflectionClass as CoreReflectionClass;
use ReflectionObject as CoreReflectionObject;
use Roave\BetterReflection\Reflection\Adapter\ReflectionObject as ReflectionObjectAdapter;
use Roave\BetterReflection\Reflection\ReflectionObject as BetterReflectionObject;
use Roave\BetterReflection\Reflection\ReflectionClass as BetterReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionMethod as BetterReflectionMethod;
use Roave\BetterReflection\Reflection\ReflectionProperty as BetterReflectionProperty;

/**
 * @covers \Roave\BetterReflection\Reflection\Adapter\ReflectionObject
 */
class ReflectionObjectTest extends \PHPUnit\Framework\TestCase
{
    public function coreReflectionMethodNamesProvider() : array
    {
        $methods = \get_class_methods(CoreReflectionObject::class);
        return \array_combine($methods, \array_map(function (string $i) : array {
            return [$i];
        }, $methods));
    }

    /**
     * @param string $methodName
     * @dataProvider coreReflectionMethodNamesProvider
     */
    public function testCoreReflectionMethods(string $methodName) : void
    {
        $reflectionObjectAdapterReflection = new CoreReflectionClass(ReflectionObjectAdapter::class);
        self::assertTrue($reflectionObjectAdapterReflection->hasMethod($methodName));
    }

    public function methodExpectationProvider() : array
    {
        $mockMethod = $this->createMock(BetterReflectionMethod::class);

        $mockProperty = $this->createMock(BetterReflectionProperty::class);

        $mockClassLike = $this->createMock(BetterReflectionClass::class);

        return [
            ['__toString', null, '', []],
            ['getName', null, '', []],
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
            ['isInstance', null, true, [new \stdClass()]],
            ['newInstance', NotImplemented::class, null, ['foo']],
            ['newInstanceWithoutConstructor', NotImplemented::class, null, []],
            ['newInstanceArgs', NotImplemented::class, null, []],
            ['getParentClass', null, $mockClassLike, []],
            ['isSubclassOf', null, true, ['\stdClass']],
            ['getStaticProperties', NotImplemented::class, null, []],
            ['getStaticPropertyValue', NotImplemented::class, null, ['foo']],
            ['setStaticPropertyValue', NotImplemented::class, null, ['foo', 'bar']],
            ['getDefaultProperties', null, ['foo' => 'bar'], []],
            ['isIterateable', null, true, []],
            ['implementsInterface', null, true, ['\Traversable']],
            ['getExtension', NotImplemented::class, null, []],
            ['getExtensionName', NotImplemented::class, null, []],
            ['inNamespace', null, true, []],
            ['getNamespaceName', null, '', []],
            ['getShortName', null, '', []],
        ];
    }

    /**
     * @param string $methodName
     * @param string|null $expectedException
     * @param mixed $returnValue
     * @param array $args
     * @dataProvider methodExpectationProvider
     */
    public function testAdapterMethods(string $methodName, $expectedException, $returnValue, array $args) : void
    {
        /* @var BetterReflectionObject|\PHPUnit_Framework_MockObject_MockObject $reflectionStub */
        $reflectionStub = $this->createMock(BetterReflectionObject::class);

        if (null === $expectedException) {
            $reflectionStub->expects($this->once())
                ->method($methodName)
                ->with(...$args)
                ->will($this->returnValue($returnValue));
        }

        if (null !== $expectedException) {
            $this->expectException($expectedException);
        }

        $adapter = new ReflectionObjectAdapter($reflectionStub);
        $adapter->{$methodName}(...$args);
    }

    public function testExport() : void
    {
        $exported = ReflectionObjectAdapter::export(new \stdClass());

        self::assertInternalType('string', $exported);
        self::assertContains('stdClass', $exported);
    }

    public function testGetFileNameReturnsFalseWhenNoFileName() : void
    {
        $betterReflectionObject = $this->createMock(BetterReflectionObject::class);
        $betterReflectionObject
            ->method('getFileName')
            ->willReturn(null);

        $betterReflectionObject = new ReflectionObjectAdapter($betterReflectionObject);

        self::assertFalse($betterReflectionObject->getFileName());
    }

    public function testGetDocCommentReturnsFalseWhenNoDocComment() : void
    {
        $betterReflectionObject = $this->createMock(BetterReflectionObject::class);
        $betterReflectionObject
            ->method('getDocComment')
            ->willReturn('');

        $reflectionObjectAdapter = new ReflectionObjectAdapter($betterReflectionObject);

        self::assertFalse($reflectionObjectAdapter->getDocComment());
    }

    public function testGetParentObjectReturnsFalseWhenNoParent() : void
    {
        $betterReflectionObject = $this->createMock(BetterReflectionObject::class);
        $betterReflectionObject
            ->method('getParentClass')
            ->willReturn(null);

        $reflectionObjectAdapter = new ReflectionObjectAdapter($betterReflectionObject);

        self::assertFalse($reflectionObjectAdapter->getParentClass());
    }

    public function testHasMethodIsCaseInsensitive() : void
    {
        $betterReflectionMethod = $this->createMock(BetterReflectionMethod::class);
        $betterReflectionMethod
            ->method('getName')
            ->willReturn('foo');

        $betterReflectionObject = $this->createMock(BetterReflectionObject::class);
        $betterReflectionObject
            ->method('getMethods')
            ->willReturn([
                $betterReflectionMethod,
            ]);
        $betterReflectionObject
            ->method('hasMethod')
            ->with('foo')
            ->willReturn(true);

        $reflectionObjectAdapter = new ReflectionObjectAdapter($betterReflectionObject);

        self::assertTrue($reflectionObjectAdapter->hasMethod('foo'));
        self::assertTrue($reflectionObjectAdapter->hasMethod('FOO'));
    }

    public function testGetMethodIsCaseInsensitive() : void
    {
        $betterReflectionMethod = $this->createMock(BetterReflectionMethod::class);
        $betterReflectionMethod
            ->method('getName')
            ->willReturn('foo');

        $betterReflectionObject = $this->createMock(BetterReflectionObject::class);
        $betterReflectionObject
            ->method('getMethods')
            ->willReturn([
                $betterReflectionMethod,
            ]);
        $betterReflectionObject
            ->method('getMethod')
            ->with('foo')
            ->willReturn($betterReflectionMethod);

        $reflectionObjectAdapter = new ReflectionObjectAdapter($betterReflectionObject);

        self::assertSame('foo', $reflectionObjectAdapter->getMethod('foo')->getName());
        self::assertSame('foo', $reflectionObjectAdapter->getMethod('FOO')->getName());
    }

    public function testIsSubclassOfIsCaseInsensitive() : void
    {
        $betterReflectionObject = $this->createMock(BetterReflectionObject::class);
        $betterReflectionObject
            ->method('getParentClassNames')
            ->willReturn(['Foo']);
        $betterReflectionObject
            ->method('isSubclassOf')
            ->with('Foo')
            ->willReturn(true);

        $reflectionObjectAdapter = new ReflectionObjectAdapter($betterReflectionObject);

        self::assertTrue($reflectionObjectAdapter->isSubclassOf('Foo'));
        self::assertTrue($reflectionObjectAdapter->isSubclassOf('foo'));
    }

    public function testImplementsInterfaceIsCaseInsensitive() : void
    {
        $betterReflectionObject = $this->createMock(BetterReflectionObject::class);
        $betterReflectionObject
            ->method('getInterfaceNames')
            ->willReturn(['Foo']);
        $betterReflectionObject
            ->method('implementsInterface')
            ->with('Foo')
            ->willReturn(true);

        $reflectionObjectAdapter = new ReflectionObjectAdapter($betterReflectionObject);

        self::assertTrue($reflectionObjectAdapter->implementsInterface('Foo'));
        self::assertTrue($reflectionObjectAdapter->implementsInterface('foo'));
    }
}
