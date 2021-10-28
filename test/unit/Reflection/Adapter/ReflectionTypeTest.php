<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection\Adapter;

use PHPUnit\Framework\TestCase;
use ReflectionClass as CoreReflectionClass;
use ReflectionType as CoreReflectionType;
use Roave\BetterReflection\Reflection\Adapter\ReflectionIntersectionType as ReflectionIntersectionTypeAdapter;
use Roave\BetterReflection\Reflection\Adapter\ReflectionNamedType as ReflectionNamedTypeAdapter;
use Roave\BetterReflection\Reflection\Adapter\ReflectionType as ReflectionTypeAdapter;
use Roave\BetterReflection\Reflection\Adapter\ReflectionUnionType as ReflectionUnionTypeAdapter;
use Roave\BetterReflection\Reflection\ReflectionIntersectionType as BetterReflectionIntersectionType;
use Roave\BetterReflection\Reflection\ReflectionNamedType as BetterReflectionNamedType;
use Roave\BetterReflection\Reflection\ReflectionUnionType as BetterReflectionUnionType;

use function array_combine;
use function array_map;
use function get_class_methods;

/**
 * @covers \Roave\BetterReflection\Reflection\Adapter\ReflectionType
 */
class ReflectionTypeTest extends TestCase
{
    public function coreReflectionMethodNamesProvider(): array
    {
        $methods = get_class_methods(CoreReflectionType::class);

        return array_combine($methods, array_map(static fn (string $i): array => [$i], $methods));
    }

    /**
     * @dataProvider coreReflectionMethodNamesProvider
     */
    public function testCoreReflectionMethods(string $methodName): void
    {
        $reflectionTypeAdapterReflection = new CoreReflectionClass(ReflectionNamedTypeAdapter::class);

        self::assertTrue($reflectionTypeAdapterReflection->hasMethod($methodName));
        self::assertSame(ReflectionNamedTypeAdapter::class, $reflectionTypeAdapterReflection->getMethod($methodName)->getDeclaringClass()->getName());
    }

    public function methodExpectationProvider(): array
    {
        return [
            ['__toString', null, '', []],
            ['allowsNull', null, true, []],
        ];
    }

    /**
     * @param list<mixed> $args
     *
     * @dataProvider methodExpectationProvider
     */
    public function testAdapterMethods(string $methodName, ?string $expectedException, mixed $returnValue, array $args): void
    {
        $reflectionStub = $this->createMock(BetterReflectionNamedType::class);

        if ($expectedException === null) {
            $reflectionStub->expects($this->once())
                ->method($methodName)
                ->with(...$args)
                ->will($this->returnValue($returnValue));
        }

        if ($expectedException !== null) {
            $this->expectException($expectedException);
        }

        $adapter = ReflectionTypeAdapter::fromTypeOrNull($reflectionStub);
        $adapter->{$methodName}(...$args);
    }

    public function testFromTypeOrNullWithNull(): void
    {
        self::assertNull(ReflectionTypeAdapter::fromTypeOrNull(null));
    }

    public function testFromTypeOrNullWithNamedType(): void
    {
        self::assertInstanceOf(ReflectionNamedTypeAdapter::class, ReflectionTypeAdapter::fromTypeOrNull($this->createMock(BetterReflectionNamedType::class)));
    }

    public function testFromTypeOrNullWithUnionType(): void
    {
        self::assertInstanceOf(ReflectionUnionTypeAdapter::class, ReflectionTypeAdapter::fromTypeOrNull($this->createMock(BetterReflectionUnionType::class)));
    }

    public function testFromTypeOrNullWithIntersectionType(): void
    {
        self::assertInstanceOf(ReflectionIntersectionTypeAdapter::class, ReflectionTypeAdapter::fromTypeOrNull($this->createMock(BetterReflectionIntersectionType::class)));
    }
}
