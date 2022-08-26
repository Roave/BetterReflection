<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection\Adapter;

use PHPUnit\Framework\TestCase;
use ReflectionClass as CoreReflectionClass;
use ReflectionIntersectionType as CoreReflectionIntersectionType;
use Roave\BetterReflection\Reflection\Adapter\ReflectionIntersectionType as ReflectionIntersectionTypeAdapter;
use Roave\BetterReflection\Reflection\ReflectionIntersectionType as BetterReflectionIntersectionType;

use function array_combine;
use function array_map;
use function get_class_methods;

/** @covers \Roave\BetterReflection\Reflection\Adapter\ReflectionIntersectionType */
class ReflectionIntersectionTypeTest extends TestCase
{
    /** @return array<string, array{0: string}> */
    public function coreReflectionMethodNamesProvider(): array
    {
        $methods = get_class_methods(CoreReflectionIntersectionType::class);

        return array_combine($methods, array_map(static fn (string $i): array => [$i], $methods));
    }

    /** @dataProvider coreReflectionMethodNamesProvider */
    public function testCoreReflectionMethods(string $methodName): void
    {
        $reflectionTypeAdapterReflection = new CoreReflectionClass(ReflectionIntersectionTypeAdapter::class);

        self::assertTrue($reflectionTypeAdapterReflection->hasMethod($methodName));
        self::assertSame(ReflectionIntersectionTypeAdapter::class, $reflectionTypeAdapterReflection->getMethod($methodName)->getDeclaringClass()->getName());
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
        $reflectionStub = $this->createMock(BetterReflectionIntersectionType::class);

        if ($expectedException === null) {
            $reflectionStub->expects($this->once())
                ->method($methodName)
                ->with(...$args)
                ->will($this->returnValue($returnValue));
        }

        if ($expectedException !== null) {
            $this->expectException($expectedException);
        }

        $adapter = new ReflectionIntersectionTypeAdapter($reflectionStub);
        $adapter->{$methodName}(...$args);
    }
}
