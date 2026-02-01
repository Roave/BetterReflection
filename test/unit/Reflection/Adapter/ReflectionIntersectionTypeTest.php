<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection\Adapter;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass as CoreReflectionClass;
use ReflectionIntersectionType as CoreReflectionIntersectionType;
use Roave\BetterReflection\Reflection\Adapter\ReflectionIntersectionType as ReflectionIntersectionTypeAdapter;
use Roave\BetterReflection\Reflection\Adapter\ReflectionNamedType as ReflectionNamedTypeAdapter;
use Roave\BetterReflection\Reflection\ReflectionIntersectionType as BetterReflectionIntersectionType;
use Roave\BetterReflection\Reflection\ReflectionNamedType as BetterReflectionNamedType;
use Throwable;

use function array_combine;
use function array_map;
use function get_class_methods;

#[CoversClass(ReflectionIntersectionTypeAdapter::class)]
class ReflectionIntersectionTypeTest extends TestCase
{
    /** @return array<string, array{0: string}> */
    public static function coreReflectionMethodNamesProvider(): array
    {
        $methods = get_class_methods(CoreReflectionIntersectionType::class);

        return array_combine($methods, array_map(static fn (string $i): array => [$i], $methods));
    }

    #[DataProvider('coreReflectionMethodNamesProvider')]
    public function testCoreReflectionMethods(string $methodName): void
    {
        $reflectionTypeAdapterReflection = new CoreReflectionClass(ReflectionIntersectionTypeAdapter::class);

        self::assertTrue($reflectionTypeAdapterReflection->hasMethod($methodName));
        self::assertSame(ReflectionIntersectionTypeAdapter::class, $reflectionTypeAdapterReflection->getMethod($methodName)->getDeclaringClass()->getName());
    }

    /** @return list<array{0: non-empty-string, 1: class-string|null, 2: mixed, 3: list<mixed>}> */
    public static function methodExpectationProvider(): array
    {
        return [
            ['__toString', null, 'int|string', []],
            ['allowsNull', null, false, []],
            ['getTypes', null, [], []],
        ];
    }

    /**
     * @param non-empty-string             $methodName
     * @param list<mixed>                  $args
     * @param class-string<Throwable>|null $expectedException
     */
    #[DataProvider('methodExpectationProvider')]
    public function testAdapterMethods(string $methodName, string|null $expectedException, mixed $returnValue, array $args): void
    {
        if ($expectedException === null) {
            $reflectionStub = $this->createMock(BetterReflectionIntersectionType::class);
            $reflectionStub->expects($this->once())
                ->method($methodName)
                ->with(...$args)
                ->willReturn($returnValue);
        } else {
            $reflectionStub = self::createStub(BetterReflectionIntersectionType::class);
        }

        if ($expectedException !== null) {
            $this->expectException($expectedException);
        }

        $adapter = new ReflectionIntersectionTypeAdapter($reflectionStub);
        $adapter->{$methodName}(...$args);
    }

    public function testGetTypes(): void
    {
        $betterReflectionType1 = self::createStub(BetterReflectionNamedType::class);
        $betterReflectionType2 = self::createStub(BetterReflectionNamedType::class);

        $betterReflectionIntersectionType = self::createStub(BetterReflectionIntersectionType::class);
        $betterReflectionIntersectionType
            ->method('getTypes')
            ->willReturn([
                $betterReflectionType1,
                $betterReflectionType2,
            ]);

        $reflectionUnionTypeAdapter = new ReflectionIntersectionTypeAdapter($betterReflectionIntersectionType);

        self::assertContainsOnlyInstancesOf(ReflectionNamedTypeAdapter::class, $reflectionUnionTypeAdapter->getTypes());
    }
}
