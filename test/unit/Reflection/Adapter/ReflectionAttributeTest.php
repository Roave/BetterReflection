<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection\Adapter;

use PHPUnit\Framework\TestCase;
use ReflectionAttribute as CoreReflectionAttribute;
use ReflectionClass as CoreReflectionClass;
use Roave\BetterReflection\Reflection\Adapter\Exception\NotImplemented;
use Roave\BetterReflection\Reflection\Adapter\ReflectionAttribute as ReflectionAttributeAdapter;
use Roave\BetterReflection\Reflection\ReflectionAttribute as BetterReflectionAttribute;

use function array_combine;
use function array_map;
use function get_class_methods;

/** @covers \Roave\BetterReflection\Reflection\Adapter\ReflectionAttribute */
class ReflectionAttributeTest extends TestCase
{
    /** @return array<string, array{0: string}> */
    public static function coreReflectionMethodNamesProvider(): array
    {
        $methods = get_class_methods(CoreReflectionAttribute::class);

        return array_combine($methods, array_map(static fn (string $i): array => [$i], $methods));
    }

    /** @dataProvider coreReflectionMethodNamesProvider */
    public function testCoreReflectionMethods(string $methodName): void
    {
        $reflectionTypeAdapterReflection = new CoreReflectionClass(ReflectionAttributeAdapter::class);

        self::assertTrue($reflectionTypeAdapterReflection->hasMethod($methodName));
        self::assertSame(ReflectionAttributeAdapter::class, $reflectionTypeAdapterReflection->getMethod($methodName)->getDeclaringClass()->getName());
    }

    /** @return list<array{0: string, 1: class-string|null, 2: mixed, 3: list<mixed>}> */
    public static function methodExpectationProvider(): array
    {
        return [
            ['__toString', null, '', []],
            ['getName', null, '', []],
            ['getTarget', null, 1, []],
            ['isRepeated', null, false, []],
            ['getArguments', null, [], []],
            ['newInstance', NotImplemented::class, null, []],
        ];
    }

    /**
     * @param list<mixed> $args
     *
     * @dataProvider methodExpectationProvider
     */
    public function testAdapterMethods(string $methodName, string|null $expectedException, mixed $returnValue, array $args): void
    {
        $reflectionStub = $this->createMock(BetterReflectionAttribute::class);

        if ($expectedException === null) {
            $reflectionStub->expects($this->once())
                ->method($methodName)
                ->with(...$args)
                ->will($this->returnValue($returnValue));
        }

        if ($expectedException !== null) {
            $this->expectException($expectedException);
        }

        $adapter = new ReflectionAttributeAdapter($reflectionStub);
        $adapter->{$methodName}(...$args);
    }
}
