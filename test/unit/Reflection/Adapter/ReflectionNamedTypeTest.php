<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection\Adapter;

use PhpParser\Node;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass as CoreReflectionClass;
use ReflectionNamedType as CoreReflectionNamedType;
use Roave\BetterReflection\Reflection\Adapter\ReflectionNamedType as ReflectionNamedTypeAdapter;
use Roave\BetterReflection\Reflection\ReflectionMethod as BetterReflectionMethod;
use Roave\BetterReflection\Reflection\ReflectionNamedType as BetterReflectionNamedType;
use Roave\BetterReflection\Reflector\Reflector;
use Throwable;

use function array_combine;
use function array_map;
use function get_class_methods;

#[CoversClass(ReflectionNamedTypeAdapter::class)]
class ReflectionNamedTypeTest extends TestCase
{
    /** @return array<string, array{0: string}> */
    public static function coreReflectionMethodNamesProvider(): array
    {
        $methods = get_class_methods(CoreReflectionNamedType::class);

        return array_combine($methods, array_map(static fn (string $i): array => [$i], $methods));
    }

    #[DataProvider('coreReflectionMethodNamesProvider')]
    public function testCoreReflectionMethods(string $methodName): void
    {
        $reflectionTypeAdapterReflection = new CoreReflectionClass(ReflectionNamedTypeAdapter::class);

        self::assertTrue($reflectionTypeAdapterReflection->hasMethod($methodName));
        self::assertSame(ReflectionNamedTypeAdapter::class, $reflectionTypeAdapterReflection->getMethod($methodName)->getDeclaringClass()->getName());
    }

    public function testWillRenderNullabilityMarkerWhenGiven(): void
    {
        $reflectionStub = self::createStub(BetterReflectionNamedType::class);
        $reflectionStub->method('__toString')
            ->willReturn('foo');

        self::assertSame('foo', (new ReflectionNamedTypeAdapter($reflectionStub, false))->__toString());
        self::assertSame('?foo', (new ReflectionNamedTypeAdapter($reflectionStub, true))->__toString());
    }

    /** @return list<array{0: string}> */
    public static function dataNoNullabilityMarkerForMixed(): array
    {
        return [
            ['mixed'],
            ['MiXeD'],
            ['null'],
            ['nULl'],
        ];
    }

    #[DataProvider('dataNoNullabilityMarkerForMixed')]
    public function testNoNullabilityMarkerForMixed(string $mixedType): void
    {
        $reflectionStub = self::createStub(BetterReflectionNamedType::class);
        $reflectionStub->method('getName')
            ->willReturn($mixedType);
        $reflectionStub->method('__toString')
            ->willReturn($mixedType);

        self::assertSame($mixedType, (new ReflectionNamedTypeAdapter($reflectionStub, true))->__toString());
    }

    public function testWillReportThatItAcceptsOrRejectsNull(): void
    {
        $reflectionStub = self::createStub(BetterReflectionNamedType::class);

        self::assertFalse((new ReflectionNamedTypeAdapter($reflectionStub, false))->allowsNull());
        self::assertTrue((new ReflectionNamedTypeAdapter($reflectionStub, true))->allowsNull());
    }

    /** @return list<array{0: non-empty-string, 1: class-string|null, 2: mixed, 3: list<mixed>}> */
    public static function methodExpectationProvider(): array
    {
        return [
            ['isBuiltin', null, true, []],
            ['getName', null, 'int', []],
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
            $reflectionStub = $this->createMock(BetterReflectionNamedType::class);
            $reflectionStub->expects($this->once())
                ->method($methodName)
                ->with(...$args)
                ->willReturn($returnValue);
        } else {
            $reflectionStub = self::createStub(BetterReflectionNamedType::class);
        }

        if ($expectedException !== null) {
            $this->expectException($expectedException);
        }

        $adapter = new ReflectionNamedTypeAdapter($reflectionStub, false);
        $adapter->{$methodName}(...$args);
    }

    /** @return list<array{0: string}> */
    public static function dataIsBuildin(): array
    {
        return [
            ['string', true],
            ['int', true],
            ['self', false],
            ['sElF', false],
            ['static', false],
            ['sTaTiC', false],
            ['parent', false],
            ['PaReNt', false],
        ];
    }

    #[DataProvider('dataIsBuildin')]
    public function testIsBuiltin(string $type, bool $isBuiltin): void
    {
        $reflector = self::createStub(Reflector::class);
        $owner     = self::createStub(BetterReflectionMethod::class);

        $betterReflectionNamedType = new BetterReflectionNamedType($reflector, $owner, new Node\Name($type));
        $reflectionTypeAdapter     = new ReflectionNamedTypeAdapter($betterReflectionNamedType, false);

        self::assertSame($isBuiltin, $reflectionTypeAdapter->isBuiltin());
    }

    public function testTypeWithoutBetterReflection(): void
    {
        $reflectionTypeAdapter = new ReflectionNamedTypeAdapter('never');

        self::assertSame('never', $reflectionTypeAdapter->getName());
        self::assertSame('never', $reflectionTypeAdapter->__toString());
        self::assertTrue($reflectionTypeAdapter->isBuiltin());
        self::assertFalse($reflectionTypeAdapter->allowsNull());
    }

    public function testDefaultAllowsNull(): void
    {
        $reflectionTypeAdapter = new ReflectionNamedTypeAdapter('never');

        self::assertFalse($reflectionTypeAdapter->allowsNull());
    }
}
