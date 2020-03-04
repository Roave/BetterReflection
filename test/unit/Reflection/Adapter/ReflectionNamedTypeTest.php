<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection\Adapter;

use PHPUnit\Framework\TestCase;
use ReflectionClass as CoreReflectionClass;
use ReflectionNamedType as CoreReflectionNamedType;
use Roave\BetterReflection\Reflection\Adapter\ReflectionNamedType as ReflectionNamedTypeAdapter;
use Roave\BetterReflection\Reflection\ReflectionType as BetterReflectionType;
use function array_combine;
use function array_map;
use function get_class_methods;

/**
 * @covers \Roave\BetterReflection\Reflection\Adapter\ReflectionNamedType
 */
class ReflectionNamedTypeTest extends TestCase
{
    public function coreReflectionTypeNamesProvider() : array
    {
        $methods = get_class_methods(CoreReflectionNamedType::class);

        return array_combine($methods, array_map(static function (string $i) : array {
            return [$i];
        }, $methods));
    }

    /**
     * @dataProvider coreReflectionTypeNamesProvider
     */
    public function testCoreReflectionTypes(string $methodName) : void
    {
        $reflectionTypeAdapterReflection = new CoreReflectionClass(ReflectionNamedTypeAdapter::class);
        self::assertTrue($reflectionTypeAdapterReflection->hasMethod($methodName));
    }

    public function methodExpectationProvider() : array
    {
        return [
            ['__toString', null, 'int', []],
            ['allowsNull', null, true, []],
            ['isBuiltin', null, true, []],
            ['getName', null, 'int', []],
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

        $adapter = new ReflectionNamedTypeAdapter($reflectionStub);
        $adapter->{$methodName}(...$args);
    }

    public function testIsInstanceOfCoreReflectionType() : void
    {
        $reflectionStub = $this->createMock(BetterReflectionType::class);
        $adapter        = ReflectionNamedTypeAdapter::fromReturnTypeOrNull($reflectionStub);
        $this->assertInstanceOf(CoreReflectionNamedType::class, $adapter);
    }
}
