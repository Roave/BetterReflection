<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection\Adapter;

use OutOfBoundsException;
use PhpParser\Node\Identifier;
use PHPUnit\Framework\TestCase;
use ReflectionClass as CoreReflectionClass;
use ReflectionParameter as CoreReflectionParameter;
use Roave\BetterReflection\Reflection\Adapter\ReflectionAttribute as ReflectionAttributeAdapter;
use Roave\BetterReflection\Reflection\Adapter\ReflectionClass as ReflectionClassAdapter;
use Roave\BetterReflection\Reflection\Adapter\ReflectionFunction as ReflectionFunctionAdapter;
use Roave\BetterReflection\Reflection\Adapter\ReflectionMethod as ReflectionMethodAdapter;
use Roave\BetterReflection\Reflection\Adapter\ReflectionNamedType as ReflectionNamedTypeAdapter;
use Roave\BetterReflection\Reflection\Adapter\ReflectionParameter as ReflectionParameterAdapter;
use Roave\BetterReflection\Reflection\ReflectionAttribute as BetterReflectionAttribute;
use Roave\BetterReflection\Reflection\ReflectionClass as BetterReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionFunction as BetterReflectionFunction;
use Roave\BetterReflection\Reflection\ReflectionIntersectionType as BetterReflectionIntersectionType;
use Roave\BetterReflection\Reflection\ReflectionMethod as BetterReflectionMethod;
use Roave\BetterReflection\Reflection\ReflectionNamedType as BetterReflectionNamedType;
use Roave\BetterReflection\Reflection\ReflectionParameter as BetterReflectionParameter;
use Roave\BetterReflection\Reflection\ReflectionType as BetterReflectionType;
use Roave\BetterReflection\Reflection\ReflectionUnionType as BetterReflectionUnionType;
use Roave\BetterReflection\Reflector\Reflector;
use ValueError;

use function array_combine;
use function array_map;
use function get_class_methods;
use function is_array;

/** @covers \Roave\BetterReflection\Reflection\Adapter\ReflectionParameter */
class ReflectionParameterTest extends TestCase
{
    /** @return array<string, array{0: string}> */
    public function coreReflectionMethodNamesProvider(): array
    {
        $methods = get_class_methods(CoreReflectionParameter::class);

        return array_combine($methods, array_map(static fn (string $i): array => [$i], $methods));
    }

    /** @dataProvider coreReflectionMethodNamesProvider */
    public function testCoreReflectionMethods(string $methodName): void
    {
        $reflectionParameterAdapterReflection = new CoreReflectionClass(ReflectionParameterAdapter::class);

        self::assertTrue($reflectionParameterAdapterReflection->hasMethod($methodName));
        self::assertSame(ReflectionParameterAdapter::class, $reflectionParameterAdapterReflection->getMethod($methodName)->getDeclaringClass()->getName());
    }

    /** @return list<array{0: string, 1: list<mixed>, 2: mixed, 3: string|null, 4: mixed, 5: string|null}> */
    public function methodExpectationProvider(): array
    {
        $mockFunction = $this->createMock(BetterReflectionFunction::class);

        $mockMethod = $this->createMock(BetterReflectionMethod::class);

        $mockClassLike = $this->createMock(BetterReflectionClass::class);

        $mockType = $this->createMock(BetterReflectionNamedType::class);

        $mockAttribute = $this->createMock(BetterReflectionAttribute::class);

        return [
            ['__toString', [], 'string', null, 'string', null],
            ['getName', [], 'name', null, 'name', null],
            ['isPassedByReference', [], true, null, true, null],
            ['canBePassedByValue', [], true, null, true, null],
            ['getDeclaringFunction', [], $mockFunction, null, null, ReflectionFunctionAdapter::class],
            ['getDeclaringFunction', [], $mockMethod, null, null, ReflectionMethodAdapter::class],
            ['getDeclaringClass', [], null, null, null, null],
            ['getDeclaringClass', [], $mockClassLike, null, null, ReflectionClassAdapter::class],
            ['isArray', [], true, null, true, null],
            ['isCallable', [], true, null, true, null],
            ['allowsNull', [], true, null, true, null],
            ['getPosition', [], 123, null, 123, null],
            ['isOptional', [], true, null, true, null],
            ['isVariadic', [], true, null, true, null],
            ['isDefaultValueAvailable', [], true, null, true, null],
            ['getDefaultValue', [], true, null, true, null],
            ['isDefaultValueConstant', [], true, null, true, null],
            ['getDefaultValueConstantName', [], 'foo', null, 'foo', null],
            ['hasType', [], true, null, true, null],
            ['getType', [], $mockType, null, null, ReflectionNamedTypeAdapter::class],
            ['isPromoted', [], true, null, true, null],
            ['getAttributes', [], [$mockAttribute], null, null, ReflectionAttributeAdapter::class],
        ];
    }

    /**
     * @param list<mixed> $args
     *
     * @dataProvider methodExpectationProvider
     */
    public function testAdapterMethods(
        string $methodName,
        array $args,
        mixed $returnValue,
        string|null $expectedException,
        mixed $expectedReturnValue,
        string|null $expectedReturnValueInstance,
    ): void {
        $reflectionStub = $this->createMock(BetterReflectionParameter::class);

        if ($expectedException === null) {
            $reflectionStub->expects($this->once())
                ->method($methodName)
                ->with(...$args)
                ->willReturn($returnValue);
        }

        $adapter = new ReflectionParameterAdapter($reflectionStub);

        if ($expectedException !== null) {
            $this->expectException($expectedException);
        }

        $actualReturnValue = $adapter->{$methodName}(...$args);

        if ($expectedReturnValue !== null) {
            self::assertSame($expectedReturnValue, $actualReturnValue);
        }

        if ($expectedReturnValueInstance === null) {
            return;
        }

        if (is_array($actualReturnValue)) {
            self::assertNotEmpty($actualReturnValue);
            self::assertContainsOnlyInstancesOf($expectedReturnValueInstance, $actualReturnValue);
        } else {
            self::assertInstanceOf($expectedReturnValueInstance, $actualReturnValue);
        }
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
        /** @phpstan-var class-string $someAttributeClassName */
        $someAttributeClassName = 'SomeAttribute';
        /** @phpstan-var class-string $anotherAttributeClassName */
        $anotherAttributeClassName = 'AnotherAttribute';

        $betterReflectionAttribute1 = $this->createMock(BetterReflectionAttribute::class);
        $betterReflectionAttribute1
            ->method('getName')
            ->willReturn($someAttributeClassName);
        $betterReflectionAttribute2 = $this->createMock(BetterReflectionAttribute::class);
        $betterReflectionAttribute2
            ->method('getName')
            ->willReturn($anotherAttributeClassName);

        $betterReflectionAttributes = [$betterReflectionAttribute1, $betterReflectionAttribute2];

        $betterReflectionParameter = $this->getMockBuilder(BetterReflectionParameter::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAttributes'])
            ->getMock();

        $betterReflectionParameter
            ->method('getAttributes')
            ->willReturn($betterReflectionAttributes);

        $reflectionParameterAdapter = new ReflectionParameterAdapter($betterReflectionParameter);
        $attributes                 = $reflectionParameterAdapter->getAttributes($someAttributeClassName);

        self::assertCount(1, $attributes);
        self::assertSame($someAttributeClassName, $attributes[0]->getName());
    }

    public function testGetAttributesWithInstance(): void
    {
        /** @phpstan-var class-string $className */
        $className = 'ClassName';
        /** @phpstan-var class-string $parentClassName */
        $parentClassName = 'ParentClassName';
        /** @phpstan-var class-string $interfaceName */
        $interfaceName = 'InterfaceName';

        $betterReflectionAttributeClass1 = $this->createMock(BetterReflectionClass::class);
        $betterReflectionAttributeClass1
            ->method('getName')
            ->willReturn($className);
        $betterReflectionAttributeClass1
            ->method('isSubclassOf')
            ->willReturnMap([
                [$parentClassName, true],
                [$interfaceName, false],
            ]);
        $betterReflectionAttributeClass1
            ->method('implementsInterface')
            ->willReturnMap([
                [$parentClassName, false],
                [$interfaceName, false],
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
                [$className, false],
                [$parentClassName, false],
                [$interfaceName, false],
            ]);
        $betterReflectionAttributeClass2
            ->method('implementsInterface')
            ->willReturnMap([
                [$className, false],
                [$parentClassName, false],
                [$interfaceName, true],
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
                [$className, false],
                [$parentClassName, true],
                [$interfaceName, false],
            ]);
        $betterReflectionAttributeClass3
            ->method('implementsInterface')
            ->willReturnMap([
                [$className, false],
                [$parentClassName, false],
                [$interfaceName, true],
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

        self::assertCount(1, $reflectionParameterAdapter->getAttributes($className, ReflectionAttributeAdapter::IS_INSTANCEOF));
        self::assertCount(2, $reflectionParameterAdapter->getAttributes($parentClassName, ReflectionAttributeAdapter::IS_INSTANCEOF));
        self::assertCount(2, $reflectionParameterAdapter->getAttributes($interfaceName, ReflectionAttributeAdapter::IS_INSTANCEOF));
    }

    public function testGetAttributesThrowsExceptionForInvalidFlags(): void
    {
        $betterReflectionParameter  = $this->createMock(BetterReflectionParameter::class);
        $reflectionParameterAdapter = new ReflectionParameterAdapter($betterReflectionParameter);

        self::expectException(ValueError::class);
        $reflectionParameterAdapter->getAttributes(null, 123);
    }

    public function testPropertyName(): void
    {
        $betterReflectionParameter = $this->createMock(BetterReflectionParameter::class);
        $betterReflectionParameter
            ->method('getName')
            ->willReturn('foo');

        $reflectionParameterAdapter = new ReflectionParameterAdapter($betterReflectionParameter);
        self::assertSame('foo', $reflectionParameterAdapter->name);
    }

    public function testUnknownProperty(): void
    {
        $betterReflectionParameter  = $this->createMock(BetterReflectionParameter::class);
        $reflectionParameterAdapter = new ReflectionParameterAdapter($betterReflectionParameter);
        $this->expectException(OutOfBoundsException::class);
        $this->expectExceptionMessage('Property Roave\BetterReflection\Reflection\Adapter\ReflectionParameter::$foo does not exist.');
        /** @phpstan-ignore-next-line */
        $reflectionParameterAdapter->foo;
    }

    /** @return array<array{0: BetterReflectionType|null, 1: string|null}> */
    public function getClassProvider(): array
    {
        $betterReflectionClass1 = $this->createMock(BetterReflectionClass::class);
        $betterReflectionClass1
            ->method('getName')
            ->willReturn('Foo');
        $betterReflectionClass2 = $this->createMock(BetterReflectionClass::class);
        $betterReflectionClass2
            ->method('getName')
            ->willReturn('Boo');

        $betterReflectionFunction  = $this->createMock(BetterReflectionFunction::class);
        $betterReflectionParameter = $this->createMock(BetterReflectionParameter::class);
        $betterReflectionParameter
            ->method('getDeclaringFunction')
            ->willReturn($betterReflectionFunction);

        $nullType   = new BetterReflectionNamedType(
            $this->createMock(Reflector::class),
            $betterReflectionParameter,
            new Identifier('null'),
        );
        $classType1 = $this->createMock(BetterReflectionNamedType::class);
        $classType1
            ->method('getClass')
            ->willReturn($betterReflectionClass1);
        $classType1
            ->method('allowsNull')
            ->willReturn(false);
        $classType2 = $this->createMock(BetterReflectionNamedType::class);
        $classType2
            ->method('getClass')
            ->willReturn($betterReflectionClass2);
        $classType2
            ->method('allowsNull')
            ->willReturn(false);

        $unionTypeWithMoreThanTwoTypes = $this->createMock(BetterReflectionUnionType::class);
        $unionTypeWithMoreThanTwoTypes
            ->method('getTypes')
            ->willReturn([$classType1, $classType2, $nullType]);

        $unionTypeWithTwoNonNullableTypes = $this->createMock(BetterReflectionUnionType::class);
        $unionTypeWithTwoNonNullableTypes
            ->method('getTypes')
            ->willReturn([$classType1, $classType2]);

        $unionTypeNullable = $this->createMock(BetterReflectionUnionType::class);
        $unionTypeNullable
            ->method('allowsNull')
            ->willReturn(true);
        $unionTypeNullable
            ->method('getTypes')
            ->willReturn([$classType1, $nullType]);

        return [
            [null, null],
            [$this->createMock(BetterReflectionIntersectionType::class), null],
            [$nullType, null],
            [$classType1, 'Foo'],
            [$unionTypeWithMoreThanTwoTypes, null],
            [$unionTypeWithTwoNonNullableTypes, null],
            [$unionTypeNullable, 'Foo'],
        ];
    }

    /** @dataProvider getClassProvider */
    public function testGetClass(BetterReflectionType|null $type, string|null $className): void
    {
        $betterReflectionParameter = $this->createMock(BetterReflectionParameter::class);
        $betterReflectionParameter
            ->method('getType')
            ->willReturn($type);

        $reflectionParameterAdapter = new ReflectionParameterAdapter($betterReflectionParameter);

        if ($className === null) {
            self::assertNull($reflectionParameterAdapter->getClass());
        } else {
            self::assertInstanceOf(ReflectionClassAdapter::class, $reflectionParameterAdapter->getClass());
            self::assertSame($className, $reflectionParameterAdapter->getClass()->getName());
        }
    }
}
