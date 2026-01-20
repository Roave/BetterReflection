<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection\Adapter;

use OutOfBoundsException;
use PhpParser\Node\Identifier;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
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
use Roave\BetterReflection\Reflection\ReflectionUnionType as BetterReflectionUnionType;
use Roave\BetterReflection\Reflector\Reflector;
use Throwable;
use ValueError;

use function array_combine;
use function array_map;
use function get_class_methods;
use function sprintf;

#[CoversClass(ReflectionParameterAdapter::class)]
class ReflectionParameterTest extends TestCase
{
    /** @return array<string, array{0: string}> */
    public static function coreReflectionMethodNamesProvider(): array
    {
        $methods = get_class_methods(CoreReflectionParameter::class);

        return array_combine($methods, array_map(static fn (string $i): array => [$i], $methods));
    }

    #[DataProvider('coreReflectionMethodNamesProvider')]
    public function testCoreReflectionMethods(string $methodName): void
    {
        $reflectionParameterAdapterReflection = new CoreReflectionClass(ReflectionParameterAdapter::class);

        self::assertTrue($reflectionParameterAdapterReflection->hasMethod($methodName));
        self::assertSame(ReflectionParameterAdapter::class, $reflectionParameterAdapterReflection->getMethod($methodName)->getDeclaringClass()->getName());
    }

    /** @return list<array{0: non-empty-string, 1: list<mixed>, 2: mixed, 3: string|null, 4: mixed}> */
    public static function methodExpectationProvider(): array
    {
        return [
            ['__toString', [], 'string', null, 'string'],
            ['getName', [], 'name', null, 'name'],
            ['isPassedByReference', [], true, null, true],
            ['canBePassedByValue', [], true, null, true],
            ['getDeclaringClass', [], null, null, null],
            ['getDeclaringClass', [], null, null, null],
            ['allowsNull', [], true, null, true],
            ['getPosition', [], 123, null, 123],
            ['isOptional', [], true, null, true],
            ['isVariadic', [], true, null, true],
            ['isDefaultValueAvailable', [], true, null, true],
            ['getDefaultValue', [], true, null, true],
            ['isDefaultValueConstant', [], true, null, true],
            ['getDefaultValueConstantName', [], 'foo', null, 'foo'],
            ['hasType', [], true, null, true],
            ['getType', [], null, null, null],
            ['isPromoted', [], true, null, true],
            ['getAttributes', [], [], null, null],
        ];
    }

    /**
     * @param non-empty-string             $methodName
     * @param list<mixed>                  $args
     * @param class-string<Throwable>|null $expectedException
     */
    #[DataProvider('methodExpectationProvider')]
    public function testAdapterMethods(
        string $methodName,
        array $args,
        mixed $returnValue,
        string|null $expectedException,
        mixed $expectedReturnValue,
    ): void {
        if ($expectedException === null) {
            $reflectionStub = $this->createMock(BetterReflectionParameter::class);
            $reflectionStub->expects($this->once())
                ->method($methodName)
                ->with(...$args)
                ->willReturn($returnValue);
        } else {
            $reflectionStub = self::createStub(BetterReflectionParameter::class);
        }

        $adapter = new ReflectionParameterAdapter($reflectionStub);

        if ($expectedException !== null) {
            $this->expectException($expectedException);
        }

        $actualReturnValue = $adapter->{$methodName}(...$args);

        if ($expectedReturnValue === null) {
            return;
        }

        self::assertSame($expectedReturnValue, $actualReturnValue);
    }

    public function testGetDeclaringFunctionWithFunction(): void
    {
        $betterReflectionParameter = self::createStub(BetterReflectionParameter::class);
        $betterReflectionParameter
            ->method('getDeclaringFunction')
            ->willReturn(self::createStub(BetterReflectionFunction::class));

        $reflectionParameterAdapter = new ReflectionParameterAdapter($betterReflectionParameter);

        self::assertInstanceOf(ReflectionFunctionAdapter::class, $reflectionParameterAdapter->getDeclaringFunction());
    }

    public function testGetDeclaringFunctionWithMethod(): void
    {
        $betterReflectionParameter = self::createStub(BetterReflectionParameter::class);
        $betterReflectionParameter
            ->method('getDeclaringFunction')
            ->willReturn(self::createStub(BetterReflectionMethod::class));

        $reflectionParameterAdapter = new ReflectionParameterAdapter($betterReflectionParameter);

        self::assertInstanceOf(ReflectionMethodAdapter::class, $reflectionParameterAdapter->getDeclaringFunction());
    }

    public function testGetDeclaringClass(): void
    {
        $betterReflectionParameter = self::createStub(BetterReflectionParameter::class);
        $betterReflectionParameter
            ->method('getDeclaringClass')
            ->willReturn(self::createStub(BetterReflectionClass::class));

        $reflectionParameterAdapter = new ReflectionParameterAdapter($betterReflectionParameter);

        self::assertInstanceOf(ReflectionClassAdapter::class, $reflectionParameterAdapter->getDeclaringClass());
    }

    public function testGetType(): void
    {
        $betterReflectionParameter = self::createStub(BetterReflectionParameter::class);
        $betterReflectionParameter
            ->method('getType')
            ->willReturn(self::createStub(BetterReflectionNamedType::class));

        $reflectionParameterAdapter = new ReflectionParameterAdapter($betterReflectionParameter);

        self::assertInstanceOf(ReflectionNamedTypeAdapter::class, $reflectionParameterAdapter->getType());
    }

    public function testGetAttributes(): void
    {
        $betterReflectionAttribute1 = self::createStub(BetterReflectionAttribute::class);
        $betterReflectionAttribute1
            ->method('getName')
            ->willReturn('SomeAttribute');
        $betterReflectionAttribute2 = self::createStub(BetterReflectionAttribute::class);
        $betterReflectionAttribute2
            ->method('getName')
            ->willReturn('AnotherAttribute');

        $betterReflectionAttributes = [$betterReflectionAttribute1, $betterReflectionAttribute2];

        $betterReflectionParameter = self::createStub(BetterReflectionParameter::class);
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

        $betterReflectionAttribute1 = self::createStub(BetterReflectionAttribute::class);
        $betterReflectionAttribute1
            ->method('getName')
            ->willReturn($someAttributeClassName);
        $betterReflectionAttribute2 = self::createStub(BetterReflectionAttribute::class);
        $betterReflectionAttribute2
            ->method('getName')
            ->willReturn($anotherAttributeClassName);

        $betterReflectionAttributes = [$betterReflectionAttribute1, $betterReflectionAttribute2];

        $betterReflectionParameter = self::getStubBuilder(BetterReflectionParameter::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAttributes'])
            ->getStub();

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

        $betterReflectionAttributeClass1 = self::createStub(BetterReflectionClass::class);
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

        $betterReflectionAttribute1 = self::createStub(BetterReflectionAttribute::class);
        $betterReflectionAttribute1
            ->method('getClass')
            ->willReturn($betterReflectionAttributeClass1);

        $betterReflectionAttributeClass2 = self::createStub(BetterReflectionClass::class);
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

        $betterReflectionAttribute2 = self::createStub(BetterReflectionAttribute::class);
        $betterReflectionAttribute2
            ->method('getClass')
            ->willReturn($betterReflectionAttributeClass2);

        $betterReflectionAttributeClass3 = self::createStub(BetterReflectionClass::class);
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

        $betterReflectionAttribute3 = self::createStub(BetterReflectionAttribute::class);
        $betterReflectionAttribute3
            ->method('getClass')
            ->willReturn($betterReflectionAttributeClass3);

        $betterReflectionAttributes = [
            $betterReflectionAttribute1,
            $betterReflectionAttribute2,
            $betterReflectionAttribute3,
        ];

        $betterReflectionParameter = self::getStubBuilder(BetterReflectionParameter::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAttributes'])
            ->getStub();

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
        $betterReflectionParameter  = self::createStub(BetterReflectionParameter::class);
        $reflectionParameterAdapter = new ReflectionParameterAdapter($betterReflectionParameter);

        $this->expectException(ValueError::class);
        $reflectionParameterAdapter->getAttributes(null, 123);
    }

    public function testPropertyName(): void
    {
        $betterReflectionParameter = self::createStub(BetterReflectionParameter::class);
        $betterReflectionParameter
            ->method('getName')
            ->willReturn('foo');

        $reflectionParameterAdapter = new ReflectionParameterAdapter($betterReflectionParameter);
        self::assertSame('foo', $reflectionParameterAdapter->name);
    }

    public function testUnknownProperty(): void
    {
        $betterReflectionParameter  = self::createStub(BetterReflectionParameter::class);
        $reflectionParameterAdapter = new ReflectionParameterAdapter($betterReflectionParameter);
        $this->expectException(OutOfBoundsException::class);
        $this->expectExceptionMessage('Property Roave\BetterReflection\Reflection\Adapter\ReflectionParameter::$foo does not exist.');
        /** @phpstan-ignore property.notFound, expr.resultUnused */
        $reflectionParameterAdapter->foo;
    }

    public function testGetClass(): void
    {
        $betterReflectionClass1 = self::createStub(BetterReflectionClass::class);
        $betterReflectionClass1
            ->method('getName')
            ->willReturn('Foo');
        $betterReflectionClass2 = self::createStub(BetterReflectionClass::class);
        $betterReflectionClass2
            ->method('getName')
            ->willReturn('Boo');
        $betterReflectionClass3 = self::createStub(BetterReflectionClass::class);
        $betterReflectionClass3
            ->method('getName')
            ->willReturn('Doo');

        $betterReflectionFunction = self::createStub(BetterReflectionFunction::class);
        $typeParameter            = self::createStub(BetterReflectionParameter::class);
        $typeParameter
            ->method('getDeclaringFunction')
            ->willReturn($betterReflectionFunction);

        $nullType   = new BetterReflectionNamedType(
            self::createStub(Reflector::class),
            $typeParameter,
            new Identifier('null'),
        );
        $classType1 = self::createStub(BetterReflectionNamedType::class);
        $classType1
            ->method('getClass')
            ->willReturn($betterReflectionClass1);
        $classType1
            ->method('allowsNull')
            ->willReturn(false);
        $classType2 = self::createStub(BetterReflectionNamedType::class);
        $classType2
            ->method('getClass')
            ->willReturn($betterReflectionClass2);
        $classType2
            ->method('allowsNull')
            ->willReturn(false);
        $classType3 = self::createStub(BetterReflectionNamedType::class);
        $classType3
            ->method('getClass')
            ->willReturn($betterReflectionClass3);
        $classType3
            ->method('allowsNull')
            ->willReturn(true);
        $intersectionType = self::createStub(BetterReflectionIntersectionType::class);

        $unionTypeWithMoreThanTwoTypes = self::createStub(BetterReflectionUnionType::class);
        $unionTypeWithMoreThanTwoTypes
            ->method('getTypes')
            ->willReturn([$classType1, $classType2, $nullType]);

        $unionTypeWithTwoNonNullableTypes = self::createStub(BetterReflectionUnionType::class);
        $unionTypeWithTwoNonNullableTypes
            ->method('getTypes')
            ->willReturn([$classType1, $classType2]);

        $unionTypeNullable = self::createStub(BetterReflectionUnionType::class);
        $unionTypeNullable
            ->method('allowsNull')
            ->willReturn(true);
        $unionTypeNullable
            ->method('getTypes')
            ->willReturn([$classType1, $nullType]);

        $unionWithIntersection = self::createStub(BetterReflectionUnionType::class);
        $unionWithIntersection
            ->method('allowsNull')
            ->willReturn(true);
        $unionWithIntersection
            ->method('getTypes')
            ->willReturn([$intersectionType, $classType1]);

        $unionWithIntersection2 = self::createStub(BetterReflectionUnionType::class);
        $unionWithIntersection2
            ->method('allowsNull')
            ->willReturn(true);
        $unionWithIntersection2
            ->method('getTypes')
            ->willReturn([$classType3, $intersectionType]);

        $types = [
            [null, null],
            [$intersectionType, null],
            [$nullType, null],
            [$classType1, 'Foo'],
            [$unionTypeWithMoreThanTwoTypes, null],
            [$unionTypeWithTwoNonNullableTypes, null],
            [$unionTypeNullable, 'Foo'],
            [$unionWithIntersection, null],
            [$unionWithIntersection2, null],
        ];

        foreach ($types as $typeNo => [$type, $typeClassName]) {
            $betterReflectionParameter = self::createStub(BetterReflectionParameter::class);
            $betterReflectionParameter
                ->method('getType')
                ->willReturn($type);

            $reflectionParameterAdapter = new ReflectionParameterAdapter($betterReflectionParameter);

            if ($typeClassName === null) {
                self::assertNull($reflectionParameterAdapter->getClass(), sprintf('Type %d', $typeNo));
            } else {
                self::assertInstanceOf(ReflectionClassAdapter::class, $reflectionParameterAdapter->getClass(), sprintf('Type %d', $typeNo));
                self::assertSame($typeClassName, $reflectionParameterAdapter->getClass()->getName(), sprintf('Type %d', $typeNo));
            }
        }
    }

    public function testIsArray(): void
    {
        $reflector     = self::createStub(Reflector::class);
        $typeParameter = self::createStub(BetterReflectionParameter::class);

        $nullType           = new BetterReflectionNamedType(
            $reflector,
            $typeParameter,
            new Identifier('null'),
        );
        $boolType           = new BetterReflectionNamedType(
            $reflector,
            $typeParameter,
            new Identifier('bool'),
        );
        $arrayType          = new BetterReflectionNamedType(
            $reflector,
            $typeParameter,
            new Identifier('array'),
        );
        $upperCaseArrayType = new BetterReflectionNamedType(
            $reflector,
            $typeParameter,
            new Identifier('ARRAY'),
        );
        $nullableArrayType  = self::createStub(BetterReflectionUnionType::class);
        $nullableArrayType
            ->method('getTypes')
            ->willReturn([$arrayType, $nullType]);
        $unionTypeWithMoreThanTwoTypes = self::createStub(BetterReflectionUnionType::class);
        $unionTypeWithMoreThanTwoTypes
            ->method('getTypes')
            ->willReturn([$boolType, $arrayType, $nullType]);
        $unionTypeWithTwoNonNullableTypes = self::createStub(BetterReflectionUnionType::class);
        $unionTypeWithTwoNonNullableTypes
            ->method('getTypes')
            ->willReturn([$boolType, $arrayType]);

        $types = [
            [null, false],
            [$nullType, false],
            [$boolType, false],
            [$arrayType, true],
            [$upperCaseArrayType, true],
            [$nullableArrayType, true],
            [$unionTypeWithMoreThanTwoTypes, false],
            [$unionTypeWithTwoNonNullableTypes, false],
            [self::createStub(BetterReflectionIntersectionType::class), false],
        ];

        foreach ($types as $typeNo => [$type, $isArray]) {
            $betterReflectionParameter = self::createStub(BetterReflectionParameter::class);
            $betterReflectionParameter
                ->method('getType')
                ->willReturn($type);

            $reflectionParameterAdapter = new ReflectionParameterAdapter($betterReflectionParameter);

            self::assertSame($isArray, $reflectionParameterAdapter->isArray(), sprintf('Type %d', $typeNo));
        }
    }

    public function testIsCallable(): void
    {
        $reflector                 = self::createStub(Reflector::class);
        $betterReflectionParameter = self::createStub(BetterReflectionParameter::class);

        $nullType              = new BetterReflectionNamedType(
            $reflector,
            $betterReflectionParameter,
            new Identifier('null'),
        );
        $boolType              = new BetterReflectionNamedType(
            $reflector,
            $betterReflectionParameter,
            new Identifier('bool'),
        );
        $callableType          = new BetterReflectionNamedType(
            $reflector,
            $betterReflectionParameter,
            new Identifier('callable'),
        );
        $upperCaseCallableType = new BetterReflectionNamedType(
            $reflector,
            $betterReflectionParameter,
            new Identifier('CALLABLE'),
        );
        $nullableArrayType     = self::createStub(BetterReflectionUnionType::class);
        $nullableArrayType
            ->method('getTypes')
            ->willReturn([$callableType, $nullType]);
        $unionTypeWithMoreThanTwoTypes = self::createStub(BetterReflectionUnionType::class);
        $unionTypeWithMoreThanTwoTypes
            ->method('getTypes')
            ->willReturn([$boolType, $callableType, $nullType]);
        $unionTypeWithTwoNonNullableTypes = self::createStub(BetterReflectionUnionType::class);
        $unionTypeWithTwoNonNullableTypes
            ->method('getTypes')
            ->willReturn([$boolType, $callableType]);

        $types = [
            [null, false],
            [$nullType, false],
            [$boolType, false],
            [$callableType, true],
            [$upperCaseCallableType, true],
            [$nullableArrayType, true],
            [$unionTypeWithMoreThanTwoTypes, false],
            [$unionTypeWithTwoNonNullableTypes, false],
            [self::createStub(BetterReflectionIntersectionType::class), false],
        ];

        foreach ($types as $typeNo => [$type, $isCallable]) {
            $betterReflectionParameter = self::createStub(BetterReflectionParameter::class);
            $betterReflectionParameter
                ->method('getType')
                ->willReturn($type);

            $reflectionParameterAdapter = new ReflectionParameterAdapter($betterReflectionParameter);

            self::assertSame($isCallable, $reflectionParameterAdapter->isCallable(), sprintf('Type %d', $typeNo));
        }
    }
}
