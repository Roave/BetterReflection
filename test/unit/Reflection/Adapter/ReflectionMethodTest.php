<?php

namespace BetterReflectionTest\Reflection\Adapter;

use BetterReflection\Reflection\Adapter\Exception\NotImplemented;
use ReflectionClass as CoreReflectionClass;
use ReflectionMethod as CoreReflectionMethod;
use BetterReflection\Reflection\Adapter\ReflectionMethod as ReflectionMethodAdapter;
use BetterReflection\Reflection\ReflectionMethod as BetterReflectionMethod;
use BetterReflection\Reflection\ReflectionParameter as BetterReflectionParameter;
use BetterReflection\Reflection\ReflectionClass as BetterReflectionClass;

/**
 * @covers \BetterReflection\Reflection\Adapter\ReflectionMethod
 */
class ReflectionMethodTest extends \PHPUnit_Framework_TestCase
{
    public function coreReflectionMethodNamesProvider()
    {
        $methods = get_class_methods(CoreReflectionMethod::class);
        return array_combine($methods, array_map(function ($i) { return [$i]; }, $methods));
    }

    /**
     * @param string $methodName
     * @dataProvider coreReflectionMethodNamesProvider
     */
    public function testCoreReflectionMethods($methodName)
    {
        $reflectionMethodAdapterReflection = new CoreReflectionClass(ReflectionMethodAdapter::class);
        $this->assertTrue($reflectionMethodAdapterReflection->hasMethod($methodName));
    }

    public function methodExpectationProvider()
    {
        $mockParameter = $this->getMockBuilder(BetterReflectionParameter::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockClassLike = $this->getMockBuilder(BetterReflectionClass::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockMethod = $this->getMockBuilder(BetterReflectionMethod::class)
            ->disableOriginalConstructor()
            ->getMock();

        return [
            // Inherited
            ['__toString', null, '', []],
            ['inNamespace', null, true, []],
            ['isClosure', null, true, []],
            ['isDeprecated', null, true, []],
            ['isInternal', null, true, []],
            ['isUserDefined', null, true, []],
            ['getClosureThis', NotImplemented::class, null, []],
            ['getClosureScopeClass', NotImplemented::class, null, []],
            ['getDocComment', null, '', []],
            ['getStartLine', null, 123, []],
            ['getEndLine', null, 123, []],
            ['getExtension', NotImplemented::class, null, []],
            ['getExtensionName', NotImplemented::class, null, []],
            ['getFileName', null, '', []],
            ['getName', null, '', []],
            ['getNamespaceName', null, '', []],
            ['getNumberOfParameters', null, 123, []],
            ['getNumberOfRequiredParameters', null, 123, []],
            ['getParameters', null, [$mockParameter], []],
            ['getShortName', null, '', []],
            ['getStaticVariables', NotImplemented::class, null, []],
            ['returnsReference', null, true, []],
            ['isGenerator', null, true, []],
            ['isVariadic', null, true, []],

            // ReflectionMethod
            ['isPublic', null, true, []],
            ['isPrivate', null, true, []],
            ['isProtected', null, true, []],
            ['isAbstract', null, true, []],
            ['isFinal', null, true, []],
            ['isStatic', null, true, []],
            ['isConstructor', null, true, []],
            ['isDestructor', null, true, []],
            ['getClosure', NotImplemented::class, null, [new \stdClass()]],
            ['getModifiers', null, 123, []],
            ['invoke', NotImplemented::class, null, [new \stdClass(), '']],
            ['invokeArgs', NotImplemented::class, null, [new \stdClass(), []]],
            ['getDeclaringClass', null, $mockClassLike, []],
            ['getPrototype', null, $mockMethod, []],
            ['setAccessible', NotImplemented::class, null, [true]],
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
        /* @var BetterReflectionMethod|\PHPUnit_Framework_MockObject_MockObject $reflectionStub */
        $reflectionStub = $this->getMockBuilder(BetterReflectionMethod::class)
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

        $adapter = new ReflectionMethodAdapter($reflectionStub);
        $adapter->{$methodName}(...$args);
    }

    public function testExport()
    {
        $this->setExpectedException(\Exception::class, 'Unable to export statically');
        ReflectionMethodAdapter::export('\stdClass', 'foo');
    }
}
