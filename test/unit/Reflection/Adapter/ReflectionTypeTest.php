<?php

namespace Roave\BetterReflectionTest\Reflection\Adapter;

use ReflectionClass as CoreReflectionClass;
use ReflectionType as CoreReflectionType;
use Roave\BetterReflection\Reflection\Adapter\ReflectionType as ReflectionTypeAdapter;
use Roave\BetterReflection\Reflection\Adapter\ReflectionType;
use Roave\BetterReflection\Reflection\ReflectionType as BetterReflectionType;

/**
 * @covers \Roave\BetterReflection\Reflection\Adapter\ReflectionType
 */
class ReflectionTypeTest extends \PHPUnit_Framework_TestCase
{
    public function coreReflectionTypeNamesProvider() : array
    {
        $methods = get_class_methods(CoreReflectionType::class);
        return array_combine($methods, array_map(function ($i) { return [$i]; }, $methods));
    }

    /**
     * @param string $methodName
     * @dataProvider coreReflectionTypeNamesProvider
     */
    public function testCoreReflectionTypes(string $methodName) : void
    {
        $reflectionTypeAdapterReflection = new CoreReflectionClass(ReflectionTypeAdapter::class);
        self::assertTrue($reflectionTypeAdapterReflection->hasMethod($methodName));
    }

    public function methodExpectationProvider() : array
    {
        return [
            ['__toString', null, '', []],
            ['allowsNull', null, true, []],
            ['isBuiltin', null, true, []],
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
        /* @var BetterReflectionType|\PHPUnit_Framework_MockObject_MockObject $reflectionStub */
        $reflectionStub = $this->createMock(BetterReflectionType::class);

        if (null === $expectedException) {
            $reflectionStub->expects($this->once())
                ->method($methodName)
                ->with(...$args)
                ->will($this->returnValue($returnValue));
        }

        if (null !== $expectedException) {
            $this->expectException($expectedException);
        }

        $adapter = new ReflectionTypeAdapter($reflectionStub);
        $adapter->{$methodName}(...$args);
    }

    public function testFromReturnTypeOrNullWithNull() : void
    {
        self::assertNull(ReflectionType::fromReturnTypeOrNull(null));
    }

    public function testFromReturnTypeOrNullWithBetterReflectionType() : void
    {
        self::assertInstanceOf(ReflectionTypeAdapter::class, ReflectionType::fromReturnTypeOrNull($this->createMock(BetterReflectionType::class)));
    }
}
