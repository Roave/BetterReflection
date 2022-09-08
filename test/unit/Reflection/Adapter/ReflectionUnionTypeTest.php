<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection\Adapter;

use PHPUnit\Framework\TestCase;
use ReflectionClass as CoreReflectionClass;
use ReflectionType as CoreReflectionType;
use ReflectionUnionType as CoreReflectionUnionType;
use Roave\BetterReflection\Reflection\Adapter\ReflectionUnionType as ReflectionUnionTypeAdapter;
use Roave\BetterReflection\Reflection\ReflectionIntersectionType as BetterReflectionIntersectionType;
use Roave\BetterReflection\Reflection\ReflectionNamedType as BetterReflectionNamedType;
use Roave\BetterReflection\Reflection\ReflectionUnionType as BetterReflectionUnionType;

use function array_combine;
use function array_map;
use function get_class_methods;

/** @covers \Roave\BetterReflection\Reflection\Adapter\ReflectionUnionType */
class ReflectionUnionTypeTest extends TestCase
{
    /** @return array<string, array{0: string}> */
    public function coreReflectionMethodNamesProvider(): array
    {
        $methods = get_class_methods(CoreReflectionUnionType::class);

        return array_combine($methods, array_map(static fn (string $i): array => [$i], $methods));
    }

    /** @dataProvider coreReflectionMethodNamesProvider */
    public function testCoreReflectionMethods(string $methodName): void
    {
        $reflectionTypeAdapterReflection = new CoreReflectionClass(ReflectionUnionTypeAdapter::class);

        self::assertTrue($reflectionTypeAdapterReflection->hasMethod($methodName));
        self::assertSame(ReflectionUnionTypeAdapter::class, $reflectionTypeAdapterReflection->getMethod($methodName)->getDeclaringClass()->getName());
    }

    /** @return list<array{0: string, 1: class-string|null, 2: mixed, 3: list<mixed>}> */
    public function methodExpectationProvider(): array
    {
        return [
            ['__toString', null, 'int|string', []],
            ['allowsNull', null, true, []],
            ['getTypes', null, [], []],
        ];
    }

    /**
     * @param list<mixed> $args
     *
     * @dataProvider methodExpectationProvider
     */
    public function testAdapterMethods(string $methodName, string|null $expectedException, mixed $returnValue, array $args): void
    {
        $reflectionStub = $this->createMock(BetterReflectionUnionType::class);

        if ($expectedException === null) {
            $reflectionStub->expects($this->once())
                ->method($methodName)
                ->with(...$args)
                ->will($this->returnValue($returnValue));
        }

        if ($expectedException !== null) {
            $this->expectException($expectedException);
        }

        $adapter = new ReflectionUnionTypeAdapter($reflectionStub);
        $adapter->{$methodName}(...$args);
    }

    public function testGetTypes(): void
    {
        $betterReflectionType1 = $this->createMock(BetterReflectionNamedType::class);
        $betterReflectionType2 = $this->createMock(BetterReflectionNamedType::class);
        $betterReflectionType3 = $this->createMock(BetterReflectionNamedType::class);
        $betterReflectionType4 = $this->createMock(BetterReflectionIntersectionType::class);
        $betterReflectionType4
            ->method('getTypes')
            ->willReturn([
                $betterReflectionType1,
                $betterReflectionType2,
            ]);

        $betterReflectionUnionType = $this->createMock(BetterReflectionUnionType::class);
        $betterReflectionUnionType
            ->method('getTypes')
            ->willReturn([
                $betterReflectionType3,
                $betterReflectionType4,
            ]);

        $reflectionUnionTypeAdapter = new ReflectionUnionTypeAdapter($betterReflectionUnionType);

        self::assertContainsOnlyInstancesOf(CoreReflectionType::class, $reflectionUnionTypeAdapter->getTypes());
    }
}
