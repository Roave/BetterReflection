<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection\Adapter;

use PHPUnit\Framework\TestCase;
use ReflectionClass as CoreReflectionClass;
use ReflectionParameter as CoreReflectionParameter;
use Roave\BetterReflection\Reflection\Adapter\ReflectionAttribute as ReflectionAttributeAdapter;
use Roave\BetterReflection\Reflection\Adapter\ReflectionParameter as ReflectionParameterAdapter;
use Roave\BetterReflection\Reflection\ReflectionAttribute as BetterReflectionAttribute;
use Roave\BetterReflection\Reflection\ReflectionClass as BetterReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionFunction as BetterReflectionFunction;
use Roave\BetterReflection\Reflection\ReflectionMethod as BetterReflectionMethod;
use Roave\BetterReflection\Reflection\ReflectionNamedType as BetterReflectionNamedType;
use Roave\BetterReflection\Reflection\ReflectionParameter as BetterReflectionParameter;

use function array_combine;
use function array_map;
use function get_class_methods;

/**
 * @covers \Roave\BetterReflection\Reflection\Adapter\ReflectionParameter
 */
class ReflectionParameterTest extends TestCase
{
    public function coreReflectionMethodNamesProvider(): array
    {
        $methods = get_class_methods(CoreReflectionParameter::class);

        return array_combine($methods, array_map(static fn (string $i): array => [$i], $methods));
    }

    /**
     * @dataProvider coreReflectionMethodNamesProvider
     */
    public function testCoreReflectionMethods(string $methodName): void
    {
        $reflectionParameterAdapterReflection = new CoreReflectionClass(ReflectionParameterAdapter::class);

        self::assertTrue($reflectionParameterAdapterReflection->hasMethod($methodName));
        self::assertSame(ReflectionParameterAdapter::class, $reflectionParameterAdapterReflection->getMethod($methodName)->getDeclaringClass()->getName());
    }

    public function methodExpectationProvider(): array
    {
        $mockFunction = $this->createMock(BetterReflectionFunction::class);

        $mockMethod = $this->createMock(BetterReflectionMethod::class);

        $mockClassLike = $this->createMock(BetterReflectionClass::class);

        $mockType = $this->createMock(BetterReflectionNamedType::class);

        return [
            ['__toString', null, '', []],
            ['getName', null, '', []],
            ['isPassedByReference', null, true, []],
            ['canBePassedByValue', null, true, []],
            ['getDeclaringFunction', null, $mockFunction, []],
            ['getDeclaringFunction', null, $mockMethod, []],
            ['getDeclaringClass', null, null, []],
            ['getDeclaringClass', null, $mockClassLike, []],
            ['getClass', null, null, []],
            ['getClass', null, $mockClassLike, []],
            ['isArray', null, true, []],
            ['isCallable', null, true, []],
            ['allowsNull', null, true, []],
            ['getPosition', null, 123, []],
            ['isOptional', null, true, []],
            ['isVariadic', null, true, []],
            ['isDefaultValueAvailable', null, true, []],
            ['getDefaultValue', null, true, []],
            ['isDefaultValueConstant', null, true, []],
            ['getDefaultValueConstantName', null, 'foo', []],
            ['hasType', null, true, []],
            ['getType', null, $mockType, []],
            ['isPromoted', null, true, []],
            ['getAttributes', null, [], []],
        ];
    }

    /**
     * @param list<mixed> $args
     *
     * @dataProvider methodExpectationProvider
     */
    public function testAdapterMethods(string $methodName, ?string $expectedException, mixed $returnValue, array $args): void
    {
        $reflectionStub = $this->createMock(BetterReflectionParameter::class);

        if ($expectedException === null) {
            $reflectionStub->expects($this->once())
                ->method($methodName)
                ->with(...$args)
                ->will($this->returnValue($returnValue));
        }

        if ($expectedException !== null) {
            $this->expectException($expectedException);
        }

        $adapter = new ReflectionParameterAdapter($reflectionStub);
        $adapter->{$methodName}(...$args);
    }

    public function testGetAttributes(): void
    {
        $betterReflectionAttribute1 = $this->createMock(BetterReflectionAttribute::class);
        $betterReflectionAttribute1
            ->method('getName')
            ->willReturn('SomeAttribute');
        $betterReflectionAttribute2 = $this->createMock(BetterReflectionAttribute::class);
        $betterReflectionAttribute2
            ->method('getName')
            ->willReturn('AnotherAttribute');

        $betterReflectionAttributes = [$betterReflectionAttribute1, $betterReflectionAttribute2];

        $betterReflectionParameter = $this->createMock(BetterReflectionParameter::class);
        $betterReflectionParameter
            ->method('getAttributes')
            ->willReturn($betterReflectionAttributes);

        $reflectionParameterAdapter = new ReflectionParameterAdapter($betterReflectionParameter);
        $attributes                 = $reflectionParameterAdapter->getAttributes();

        self::assertCount(2, $attributes);
        self::assertSame('SomeAttribute', $attributes[0]->getName());
        self::assertSame('AnotherAttribute', $attributes[1]->getName());
    }

    public function testGetAttributesWithName(): void
    {
        $betterReflectionAttribute1 = $this->createMock(BetterReflectionAttribute::class);
        $betterReflectionAttribute1
            ->method('getName')
            ->willReturn('SomeAttribute');
        $betterReflectionAttribute2 = $this->createMock(BetterReflectionAttribute::class);
        $betterReflectionAttribute2
            ->method('getName')
            ->willReturn('AnotherAttribute');

        $betterReflectionAttributes = [$betterReflectionAttribute1, $betterReflectionAttribute2];

        $betterReflectionParameter = $this->getMockBuilder(BetterReflectionParameter::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAttributes'])
            ->getMock();

        $betterReflectionParameter
            ->method('getAttributes')
            ->willReturn($betterReflectionAttributes);

        $reflectionParameterAdapter = new ReflectionParameterAdapter($betterReflectionParameter);
        $attributes                 = $reflectionParameterAdapter->getAttributes('SomeAttribute');

        self::assertCount(1, $attributes);
        self::assertSame('SomeAttribute', $attributes[0]->getName());
    }

    public function testGetAttributesWithInstance(): void
    {
        $betterReflectionAttributeClass1 = $this->createMock(BetterReflectionClass::class);
        $betterReflectionAttributeClass1
            ->method('getName')
            ->willReturn('ClassName');
        $betterReflectionAttributeClass1
            ->method('isSubclassOf')
            ->willReturnMap([
                ['ParentClassName', true],
                ['InterfaceName', false],
            ]);
        $betterReflectionAttributeClass1
            ->method('implementsInterface')
            ->willReturnMap([
                ['ParentClassName', false],
                ['InterfaceName', false],
            ]);

        $betterReflectionAttribute1 = $this->createMock(BetterReflectionAttribute::class);
        $betterReflectionAttribute1
            ->method('getClass')
            ->willReturn($betterReflectionAttributeClass1);

        $betterReflectionAttributeClass2 = $this->createMock(BetterReflectionClass::class);
        $betterReflectionAttributeClass2
            ->method('getName')
            ->willReturn('Whatever');
        $betterReflectionAttributeClass2
            ->method('isSubclassOf')
            ->willReturnMap([
                ['ClassName', false],
                ['ParentClassName', false],
                ['InterfaceName', false],
            ]);
        $betterReflectionAttributeClass2
            ->method('implementsInterface')
            ->willReturnMap([
                ['ClassName', false],
                ['ParentClassName', false],
                ['InterfaceName', true],
            ]);

        $betterReflectionAttribute2 = $this->createMock(BetterReflectionAttribute::class);
        $betterReflectionAttribute2
            ->method('getClass')
            ->willReturn($betterReflectionAttributeClass2);

        $betterReflectionAttributeClass3 = $this->createMock(BetterReflectionClass::class);
        $betterReflectionAttributeClass3
            ->method('getName')
            ->willReturn('Whatever');
        $betterReflectionAttributeClass3
            ->method('isSubclassOf')
            ->willReturnMap([
                ['ClassName', false],
                ['ParentClassName', true],
                ['InterfaceName', false],
            ]);
        $betterReflectionAttributeClass3
            ->method('implementsInterface')
            ->willReturnMap([
                ['ClassName', false],
                ['ParentClassName', false],
                ['InterfaceName', true],
            ]);

        $betterReflectionAttribute3 = $this->createMock(BetterReflectionAttribute::class);
        $betterReflectionAttribute3
            ->method('getClass')
            ->willReturn($betterReflectionAttributeClass3);

        $betterReflectionAttributes = [
            $betterReflectionAttribute1,
            $betterReflectionAttribute2,
            $betterReflectionAttribute3,
        ];

        $betterReflectionParameter = $this->getMockBuilder(BetterReflectionParameter::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAttributes'])
            ->getMock();

        $betterReflectionParameter
            ->method('getAttributes')
            ->willReturn($betterReflectionAttributes);

        $reflectionParameterAdapter = new ReflectionParameterAdapter($betterReflectionParameter);

        self::assertCount(1, $reflectionParameterAdapter->getAttributes('ClassName', ReflectionAttributeAdapter::IS_INSTANCEOF));
        self::assertCount(2, $reflectionParameterAdapter->getAttributes('ParentClassName', ReflectionAttributeAdapter::IS_INSTANCEOF));
        self::assertCount(2, $reflectionParameterAdapter->getAttributes('InterfaceName', ReflectionAttributeAdapter::IS_INSTANCEOF));
    }
}
