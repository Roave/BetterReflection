<?php

namespace BetterReflectionTest\Reflection\Adapter;

use BetterReflection\Reflection\Adapter\Exception\NotImplemented;
use ReflectionClass as CoreReflectionClass;
use BetterReflection\Reflection\Adapter\ReflectionClass as ReflectionClassAdapter;
use BetterReflection\Reflection\ReflectionClass as BetterReflectionClass;
use BetterReflection\Reflection\ReflectionMethod as BetterReflectionMethod;
use BetterReflection\Reflection\ReflectionProperty as BetterReflectionProperty;

/**
 * @covers \BetterReflection\Reflection\Adapter\ReflectionClass
 */
class ReflectionClassTest extends \PHPUnit_Framework_TestCase
{
    public function coreReflectionMethodNamesProvider()
    {
        $methods = get_class_methods(CoreReflectionClass::class);
        return array_combine($methods, array_map(function ($i) { return [$i]; }, $methods));
    }

    /**
     * @param string $methodName
     * @dataProvider coreReflectionMethodNamesProvider
     */
    public function testCoreReflectionMethods($methodName)
    {
        $reflectionClassAdapterReflection = new CoreReflectionClass(ReflectionClassAdapter::class);
        $this->assertTrue($reflectionClassAdapterReflection->hasMethod($methodName));
    }

    public function methodExpectationProvider()
    {
        $mockMethod = $this->getMockBuilder(BetterReflectionMethod::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockProperty = $this->getMockBuilder(BetterReflectionProperty::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockClassLike = $this->getMockBuilder(BetterReflectionClass::class)
            ->disableOriginalConstructor()
            ->getMock();

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
            ['isInstance', null, true, [new \stdClass]],
            ['newInstance', NotImplemented::class, null, []],
            ['newInstanceWithoutConstructor', NotImplemented::class, null, []],
            ['newInstanceArgs', NotImplemented::class, null, []],
            ['getParentClass', null, $mockClassLike, []],
            ['isSubclassOf', null, true, ['\stdClass']],
            ['getStaticProperties', NotImplemented::class, null, []],
            ['getStaticPropertyValue', NotImplemented::class, null, ['foo']],
            ['setStaticPropertyValue', NotImplemented::class, null, ['foo', 'bar']],
            ['getDefaultProperties', null, [$mockProperty], []],
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
    public function testAdapterMethods($methodName, $expectedException, $returnValue, array $args)
    {
        /* @var BetterReflectionClass|\PHPUnit_Framework_MockObject_MockObject $reflectionStub */
        $reflectionStub = $this->getMockBuilder(BetterReflectionClass::class)
            ->disableOriginalConstructor()
            ->getMock();

        if (null === $expectedException) {
            $reflectionStub->expects($this->once())
                ->method($methodName)
                ->with(...$args)
                ->will($this->returnValue($returnValue));
        }

        if (null !== $expectedException) {
            $this->setExpectedException($expectedException);
        }

        $adapter = new ReflectionClassAdapter($reflectionStub);
        $adapter->{$methodName}(...$args);
    }

    public function testExport()
    {
        $exported = ReflectionClassAdapter::export('\stdClass');

        $this->assertInternalType('string', $exported);
        $this->assertContains('stdClass', $exported);
    }
}
