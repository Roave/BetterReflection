<?php
declare(strict_types=1);

namespace Rector\BetterReflectionTest\Reflection\Adapter;

use Exception;
use PHPUnit\Framework\TestCase;
use ReflectionClass as CoreReflectionClass;
use ReflectionParameter as CoreReflectionParameter;
use Rector\BetterReflection\Reflection\Adapter\ReflectionParameter as ReflectionParameterAdapter;
use Rector\BetterReflection\Reflection\ReflectionClass as BetterReflectionClass;
use Rector\BetterReflection\Reflection\ReflectionFunction as BetterReflectionFunction;
use Rector\BetterReflection\Reflection\ReflectionMethod as BetterReflectionMethod;
use Rector\BetterReflection\Reflection\ReflectionParameter as BetterReflectionParameter;
use Rector\BetterReflection\Reflection\ReflectionType as BetterReflectionType;

/**
 * @covers \Rector\BetterReflection\Reflection\Adapter\ReflectionParameter
 */
class ReflectionParameterTest extends TestCase
{
    public function coreReflectionParameterNamesProvider() : array
    {
        $methods = \get_class_methods(CoreReflectionParameter::class);
        return \array_combine($methods, \array_map(function (string $i) : array {
            return [$i];
        }, $methods));
    }

    /**
     * @param string $methodName
     * @dataProvider coreReflectionParameterNamesProvider
     */
    public function testCoreReflectionParameters(string $methodName) : void
    {
        $reflectionParameterAdapterReflection = new CoreReflectionClass(ReflectionParameterAdapter::class);
        self::assertTrue($reflectionParameterAdapterReflection->hasMethod($methodName));
    }

    public function methodExpectationProvider() : array
    {
        $mockFunction = $this->createMock(BetterReflectionFunction::class);

        $mockMethod = $this->createMock(BetterReflectionMethod::class);

        $mockClassLike = $this->createMock(BetterReflectionClass::class);

        $mockType = $this->createMock(BetterReflectionType::class);

        return [
            ['__toString', null, '', []],
            ['getName', null, '', []],
            ['isPassedByReference', null, true, []],
            ['canBePassedByValue', null, true, []],
            ['getDeclaringFunction', null, $mockFunction, []],
            ['getDeclaringFunction', null, $mockMethod, []],
            ['getDeclaringClass', null, null, []],
            ['getDeclaringClass', null, $mockClassLike, []],
            ['getClass', null, null, []],
            ['getClass', null, $mockClassLike, []],
            ['isArray', null, true, []],
            ['isCallable', null, true, []],
            ['allowsNull', null, true, []],
            ['getPosition', null, 123, []],
            ['isOptional', null, true, []],
            ['isVariadic', null, true, []],
            ['isDefaultValueAvailable', null, true, []],
            ['getDefaultValue', null, true, []],
            ['isDefaultValueConstant', null, true, []],
            ['getDefaultValueConstantName', null, 'foo', []],
            ['hasType', null, true, []],
            ['getType', null, $mockType, []],
        ];
    }

    /**
     * @param string $methodName
     * @param string|null $expectedException
     * @param mixed $returnValue
     * @param array $args
     * @dataProvider methodExpectationProvider
     */
    public function testAdapterMethods(string $methodName, ?string $expectedException, $returnValue, array $args) : void
    {
        /** @var BetterReflectionParameter|\PHPUnit_Framework_MockObject_MockObject $reflectionStub */
        $reflectionStub = $this->createMock(BetterReflectionParameter::class);

        if (null === $expectedException) {
            $reflectionStub->expects($this->once())
                ->method($methodName)
                ->with(...$args)
                ->will($this->returnValue($returnValue));
        }

        if (null !== $expectedException) {
            $this->expectException($expectedException);
        }

        $adapter = new ReflectionParameterAdapter($reflectionStub);
        $adapter->{$methodName}(...$args);
    }

    public function testExport() : void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Unable to export statically');
        ReflectionParameterAdapter::export('foo', 0);
    }
}
