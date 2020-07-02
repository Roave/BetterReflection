<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection\Adapter;

use PHPUnit\Framework\TestCase;
use ReflectionClass as CoreReflectionClass;
use ReflectionType as CoreReflectionType;
use Roave\BetterReflection\Reflection\Adapter\ReflectionType as ReflectionTypeAdapter;
use Roave\BetterReflection\Reflection\ReflectionType as BetterReflectionType;
use Roave\BetterReflection\Reflector\Reflector;

use function array_combine;
use function array_map;
use function assert;
use function get_class_methods;

/**
 * @covers \Roave\BetterReflection\Reflection\Adapter\ReflectionType
 */
class ReflectionTypeTest extends TestCase
{
    public function coreReflectionTypeNamesProvider(): array
    {
        $methods = get_class_methods(CoreReflectionType::class);

        return array_combine($methods, array_map(static function (string $i): array {
            return [$i];
        }, $methods));
    }

    /**
     * @dataProvider coreReflectionTypeNamesProvider
     */
    public function testCoreReflectionTypes(string $methodName): void
    {
        $reflectionTypeAdapterReflection = new CoreReflectionClass(ReflectionTypeAdapter::class);
        self::assertTrue($reflectionTypeAdapterReflection->hasMethod($methodName));
    }

    public function methodExpectationProvider(): array
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
     *
     * @dataProvider methodExpectationProvider
     */
    public function testAdapterMethods(string $methodName, ?string $expectedException, $returnValue, array $args): void
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

        $adapter = new ReflectionTypeAdapter($reflectionStub);
        $adapter->{$methodName}(...$args);
    }

    public function testFromReturnTypeOrNullWithNull(): void
    {
        self::assertNull(ReflectionTypeAdapter::fromReturnTypeOrNull(null));
    }

    public function testFromReturnTypeOrNullWithBetterReflectionType(): void
    {
        self::assertInstanceOf(ReflectionTypeAdapter::class, ReflectionTypeAdapter::fromReturnTypeOrNull($this->createMock(BetterReflectionType::class)));
    }

    public function testSelfIsNotBuiltin(): void
    {
        $reflector = $this->createMock(Reflector::class);
        assert($reflector instanceof Reflector);
        $betterReflectionType  = BetterReflectionType::createFromTypeAndReflector('self', false, $reflector);
        $reflectionTypeAdapter = new ReflectionTypeAdapter($betterReflectionType);

        self::assertFalse($reflectionTypeAdapter->isBuiltin());
    }

    public function testParentIsNotBuiltin(): void
    {
        $reflector = $this->createMock(Reflector::class);
        assert($reflector instanceof Reflector);
        $betterReflectionType  = BetterReflectionType::createFromTypeAndReflector('parent', false, $reflector);
        $reflectionTypeAdapter = new ReflectionTypeAdapter($betterReflectionType);

        self::assertFalse($reflectionTypeAdapter->isBuiltin());
    }
}
