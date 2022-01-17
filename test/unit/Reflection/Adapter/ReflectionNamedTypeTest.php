<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection\Adapter;

use PhpParser\Node;
use PHPUnit\Framework\TestCase;
use ReflectionClass as CoreReflectionClass;
use ReflectionNamedType as CoreReflectionNamedType;
use Roave\BetterReflection\Reflection\Adapter\ReflectionNamedType as ReflectionNamedTypeAdapter;
use Roave\BetterReflection\Reflection\ReflectionMethod as BetterReflectionMethod;
use Roave\BetterReflection\Reflection\ReflectionNamedType as BetterReflectionNamedType;
use Roave\BetterReflection\Reflector\Reflector;

use function array_combine;
use function array_map;
use function get_class_methods;

/**
 * @covers \Roave\BetterReflection\Reflection\Adapter\ReflectionNamedType
 */
class ReflectionNamedTypeTest extends TestCase
{
    public function coreReflectionMethodNamesProvider(): array
    {
        $methods = get_class_methods(CoreReflectionNamedType::class);

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

    public function testWillRenderNullabilityMarkerWhenGiven(): void
    {
        $reflectionStub = $this->createMock(BetterReflectionNamedType::class);
        $reflectionStub->method('__toString')
            ->willReturn('foo');

        self::assertSame('foo', (new ReflectionNamedTypeAdapter($reflectionStub, false))->__toString());
        self::assertSame('?foo', (new ReflectionNamedTypeAdapter($reflectionStub, true))->__toString());
    }

    public function dataNoNullabilityMarkerForMixed(): array
    {
        return [
            ['mixed'],
            ['MiXeD'],
        ];
    }

    /**
     * @dataProvider dataNoNullabilityMarkerForMixed
     */
    public function testNoNullabilityMarkerForMixed(string $mixedType): void
    {
        $reflectionStub = $this->createMock(BetterReflectionNamedType::class);
        $reflectionStub->method('getName')
            ->willReturn($mixedType);
        $reflectionStub->method('__toString')
            ->willReturn($mixedType);

        self::assertSame($mixedType, (new ReflectionNamedTypeAdapter($reflectionStub, true))->__toString());
    }

    public function testWillReportThatItAcceptsOrRejectsNull(): void
    {
        $reflectionStub = $this->createMock(BetterReflectionNamedType::class);

        self::assertFalse((new ReflectionNamedTypeAdapter($reflectionStub, false))->allowsNull());
        self::assertTrue((new ReflectionNamedTypeAdapter($reflectionStub, true))->allowsNull());
    }

    public function methodExpectationProvider(): array
    {
        return [
            ['isBuiltin', null, true, []],
            ['getName', null, 'int', []],
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

        $adapter = new ReflectionNamedTypeAdapter($reflectionStub, false);
        $adapter->{$methodName}(...$args);
    }

    public function dataNotBuildin(): array
    {
        return [
            ['self'],
            ['sElF'],
            ['static'],
            ['sTaTiC'],
            ['parent'],
            ['PaReNt'],
        ];
    }

    /**
     * @dataProvider dataNotBuildin
     */
    public function testIsNotBuiltin(string $type): void
    {
        $reflector = $this->createMock(Reflector::class);
        $owner     = $this->createMock(BetterReflectionMethod::class);

        $betterReflectionNamedType = new BetterReflectionNamedType($reflector, $owner, new Node\Name($type));
        $reflectionTypeAdapter     = new ReflectionNamedTypeAdapter($betterReflectionNamedType, false);

        self::assertFalse($reflectionTypeAdapter->isBuiltin());
    }
}
