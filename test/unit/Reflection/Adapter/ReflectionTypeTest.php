<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection\Adapter;

use PHPUnit\Framework\TestCase;
use ReflectionClass as CoreReflectionClass;
use ReflectionType as CoreReflectionType;
use Roave\BetterReflection\Reflection\Adapter\ReflectionType;
use Roave\BetterReflection\Reflection\Adapter\ReflectionType as ReflectionTypeAdapter;
use Roave\BetterReflection\Reflection\ReflectionType as BetterReflectionType;
use Roave\BetterReflection\Reflector\Reflector;
use function array_combine;
use function array_map;
use function get_class_methods;

/**
 * @covers \Roave\BetterReflection\Reflection\Adapter\ReflectionType
 */
class ReflectionTypeTest extends TestCase
{
    public function coreReflectionTypeNamesProvider() : array
    {
        $methods = get_class_methods(CoreReflectionType::class);
        return array_combine($methods, array_map(function (string $i) : array {
            return [$i];
        }, $methods));
    }

    /**
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
     * @param mixed   $returnValue
     * @param mixed[] $args
     * @dataProvider methodExpectationProvider
     */
    public function testAdapterMethods(string $methodName, ?string $expectedException, $returnValue, array $args) : void
    {
        /** @var BetterReflectionType|\PHPUnit_Framework_MockObject_MockObject $reflectionStub */
        $reflectionStub = $this->createMock(BetterReflectionType::class);

        if ($expectedException === null) {
            $reflectionStub->expects($this->once())
                ->method($methodName)
                ->with(...$args)
                ->will($this->returnValue($returnValue));
        }

        if ($expectedException !== null) {
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

    public function testSelfIsNotBuiltin() : void
    {
        /** @var Reflector $reflector */
        $reflector             = $this->createMock(Reflector::class);
        $betterReflectionType  = BetterReflectionType::createFromTypeAndReflector('self', false, $reflector);
        $reflectionTypeAdapter = new ReflectionTypeAdapter($betterReflectionType);

        self::assertFalse($reflectionTypeAdapter->isBuiltin());
    }

    public function testParentIsNotBuiltin() : void
    {
        /** @var Reflector $reflector */
        $reflector             = $this->createMock(Reflector::class);
        $betterReflectionType  = BetterReflectionType::createFromTypeAndReflector('parent', false, $reflector);
        $reflectionTypeAdapter = new ReflectionTypeAdapter($betterReflectionType);

        self::assertFalse($reflectionTypeAdapter->isBuiltin());
    }
}
