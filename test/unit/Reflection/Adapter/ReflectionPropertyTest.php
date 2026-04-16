<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection\Adapter;

use ArgumentCountError;
use OutOfBoundsException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhp;
use PHPUnit\Framework\TestCase;
use PropertyHookType;
use ReflectionClass as CoreReflectionClass;
use ReflectionException as CoreReflectionException;
use ReflectionProperty as CoreReflectionProperty;
use Roave\BetterReflection\Reflection\Adapter\Exception\NotImplementedBecauseItTriggersAutoloading;
use Roave\BetterReflection\Reflection\Adapter\ReflectionAttribute as ReflectionAttributeAdapter;
use Roave\BetterReflection\Reflection\Adapter\ReflectionClass as ReflectionClassAdapter;
use Roave\BetterReflection\Reflection\Adapter\ReflectionNamedType as ReflectionNamedTypeAdapter;
use Roave\BetterReflection\Reflection\Adapter\ReflectionProperty as ReflectionPropertyAdapter;
use Roave\BetterReflection\Reflection\Exception\NoObjectProvided;
use Roave\BetterReflection\Reflection\Exception\NotAnObject;
use Roave\BetterReflection\Reflection\Exception\ObjectNotInstanceOfClass;
use Roave\BetterReflection\Reflection\ReflectionAttribute as BetterReflectionAttribute;
use Roave\BetterReflection\Reflection\ReflectionClass as BetterReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionMethod as BetterReflectionMethod;
use Roave\BetterReflection\Reflection\ReflectionNamedType as BetterReflectionNamedType;
use Roave\BetterReflection\Reflection\ReflectionParameter as BetterReflectionParameter;
use Roave\BetterReflection\Reflection\ReflectionProperty as BetterReflectionProperty;
use stdClass;
use Throwable;
use TypeError;
use ValueError;

use function array_combine;
use function array_map;
use function get_class_methods;
use function is_array;

#[CoversClass(ReflectionPropertyAdapter::class)]
class ReflectionPropertyTest extends TestCase
{
    /** @return array<string, array{0: string}> */
    public static function coreReflectionMethodNamesProvider(): array
    {
        $methods = get_class_methods(CoreReflectionProperty::class);

        return array_combine($methods, array_map(static fn (string $i): array => [$i], $methods));
    }

    #[DataProvider('coreReflectionMethodNamesProvider')]
    public function testCoreReflectionMethods(string $methodName): void
    {
        $reflectionPropertyAdapterReflection = new CoreReflectionClass(ReflectionPropertyAdapter::class);

        self::assertTrue($reflectionPropertyAdapterReflection->hasMethod($methodName));
        self::assertSame(ReflectionPropertyAdapter::class, $reflectionPropertyAdapterReflection->getMethod($methodName)->getDeclaringClass()->getName());
    }

    /** @return list<array{0: non-empty-string, 1: list<mixed>, 2: mixed, 3: string|null, 4: mixed, 5: string|null}> */
    public static function methodExpectationProvider(): array
    {
        return [
            ['__toString', [], 'string', null, 'string', null],
            ['getName', [], 'name', null, 'name', null],
            ['isPublic', [], true, null, true, null],
            ['isPrivate', [], true, null, true, null],
            ['isPrivateSet', [], true, null, true, null],
            ['isProtected', [], true, null, true, null],
            ['isProtectedSet', [], true, null, true, null],
            ['isStatic', [], true, null, true, null],
            ['isFinal', [], true, null, true, null],
            ['isAbstract', [], true, null, true, null],
            ['isDefault', [], true, null, true, null],
            ['isDynamic', [], false, null, false, null],
            ['getModifiers', [], 123, null, 123, null],
            ['getDocComment', [], null, null, false, null],
            ['hasType', [], true, null, true, null],
            ['hasDefaultValue', [], true, null, true, null],
            ['getDefaultValue', [], null, null, null, null],
            ['isPromoted', [], true, null, true, null],
            ['isReadOnly', [], true, null, true, null],
            ['isVirtual', [], true, null, true, null],
            ['hasHooks', [], false, null, false, null],
            ['getHooks', [], [], null, [], null],
        ];
    }

    /**
     * @param non-empty-string             $methodName
     * @param list<mixed>                  $args
     * @param class-string<Throwable>|null $expectedException
     * @param class-string|null            $expectedReturnValueInstance
     */
    #[DataProvider('methodExpectationProvider')]
    public function testAdapterMethods(
        string $methodName,
        array $args,
        mixed $returnValue,
        string|null $expectedException,
        mixed $expectedReturnValue,
        string|null $expectedReturnValueInstance,
    ): void {
        if ($expectedException === null) {
            $reflectionStub = $this->createMock(BetterReflectionProperty::class);
            $reflectionStub->expects($this->once())
                ->method($methodName)
                ->with(...$args)
                ->willReturn($returnValue);
        } else {
            $reflectionStub = self::createStub(BetterReflectionProperty::class);
        }

        $adapter = new ReflectionPropertyAdapter($reflectionStub);

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

    public function testGetDocCommentReturnsFalseWhenNoDocComment(): void
    {
        $betterReflectionProperty = self::createStub(BetterReflectionProperty::class);
        $betterReflectionProperty
            ->method('getDocComment')
            ->willReturn(null);

        $reflectionPropertyAdapter = new ReflectionPropertyAdapter($betterReflectionProperty);

        self::assertFalse($reflectionPropertyAdapter->getDocComment());
    }

    public function testGetDeclaringClass(): void
    {
        $betterReflectionClass = self::createStub(BetterReflectionClass::class);
        $betterReflectionClass
            ->method('getName')
            ->willReturn('DeclaringClass');

        $betterReflectionProperty = self::createStub(BetterReflectionProperty::class);
        $betterReflectionProperty
            ->method('getImplementingClass')
            ->willReturn($betterReflectionClass);

        $reflectionPropertyAdapter = new ReflectionPropertyAdapter($betterReflectionProperty);

        self::assertInstanceOf(ReflectionClassAdapter::class, $reflectionPropertyAdapter->getDeclaringClass());
        self::assertSame('DeclaringClass', $reflectionPropertyAdapter->getDeclaringClass()->getName());
    }

    public function testGetType(): void
    {
        $betterReflectionProperty = self::createStub(BetterReflectionProperty::class);
        $betterReflectionProperty
            ->method('getType')
            ->willReturn(self::createStub(BetterReflectionNamedType::class));

        $reflectionPropertyAdapter = new ReflectionPropertyAdapter($betterReflectionProperty);

        self::assertInstanceOf(ReflectionNamedTypeAdapter::class, $reflectionPropertyAdapter->getType());
    }

    public function testGetValueReturnsNullWhenNoObject(): void
    {
        $betterReflectionProperty = self::createStub(BetterReflectionProperty::class);
        $betterReflectionProperty
            ->method('isPublic')
            ->willReturn(true);
        $betterReflectionProperty
            ->method('getValue')
            ->willThrowException(NoObjectProvided::create());

        $reflectionPropertyAdapter = new ReflectionPropertyAdapter($betterReflectionProperty);

        self::assertNull($reflectionPropertyAdapter->getValue());
    }

    public function testSetValueThrowsErrorWhenNoObject(): void
    {
        $betterReflectionProperty = self::createStub(BetterReflectionProperty::class);
        $betterReflectionProperty
            ->method('isPublic')
            ->willReturn(true);
        $betterReflectionProperty
            ->method('setValue')
            ->willThrowException(NoObjectProvided::create());

        $reflectionPropertyAdapter = new ReflectionPropertyAdapter($betterReflectionProperty);

        $this->expectException(ArgumentCountError::class);
        $reflectionPropertyAdapter->setValue(null);
    }

    public function testSetValueThrowsErrorWhenNotAnObject(): void
    {
        $betterReflectionProperty = self::createStub(BetterReflectionProperty::class);
        $betterReflectionProperty
            ->method('isPublic')
            ->willReturn(true);
        $betterReflectionProperty
            ->method('setValue')
            ->willThrowException(NotAnObject::fromNonObject('string'));

        $reflectionPropertyAdapter = new ReflectionPropertyAdapter($betterReflectionProperty);

        $this->expectException(TypeError::class);
        $reflectionPropertyAdapter->setValue('string');
    }

    public function testGetValueThrowsExceptionWhenObjectNotInstanceOfClass(): void
    {
        $betterReflectionProperty = self::createStub(BetterReflectionProperty::class);
        $betterReflectionProperty
            ->method('isPublic')
            ->willReturn(true);
        $betterReflectionProperty
            ->method('getValue')
            ->willThrowException(ObjectNotInstanceOfClass::fromClassName('Foo'));

        $reflectionPropertyAdapter = new ReflectionPropertyAdapter($betterReflectionProperty);

        $this->expectException(CoreReflectionException::class);
        $reflectionPropertyAdapter->getValue(new stdClass());
    }

    public function testSetValueThrowsExceptionWhenObjectNotInstanceOfClass(): void
    {
        $betterReflectionProperty = self::createStub(BetterReflectionProperty::class);
        $betterReflectionProperty
            ->method('isPublic')
            ->willReturn(true);
        $betterReflectionProperty
            ->method('setValue')
            ->willThrowException(ObjectNotInstanceOfClass::fromClassName('Foo'));

        $reflectionPropertyAdapter = new ReflectionPropertyAdapter($betterReflectionProperty);

        $this->expectException(CoreReflectionException::class);
        $reflectionPropertyAdapter->setValue(new stdClass());
    }

    public function testIsInitializedThrowsExceptionWhenObjectNotInstanceOfClass(): void
    {
        $betterReflectionProperty = self::createStub(BetterReflectionProperty::class);
        $betterReflectionProperty
            ->method('isPublic')
            ->willReturn(true);
        $betterReflectionProperty
            ->method('isInitialized')
            ->willThrowException(ObjectNotInstanceOfClass::fromClassName('Foo'));

        $reflectionPropertyAdapter = new ReflectionPropertyAdapter($betterReflectionProperty);

        $this->expectException(CoreReflectionException::class);

        $reflectionPropertyAdapter->isInitialized(new stdClass());
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

        $betterReflectionProperty = self::createStub(BetterReflectionProperty::class);
        $betterReflectionProperty
            ->method('getAttributes')
            ->willReturn($betterReflectionAttributes);

        $reflectionPropertyAdapter = new ReflectionPropertyAdapter($betterReflectionProperty);
        $attributes                = $reflectionPropertyAdapter->getAttributes();

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

        $betterReflectionProperty = self::getStubBuilder(BetterReflectionProperty::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAttributes'])
            ->getStub();

        $betterReflectionProperty
            ->method('getAttributes')
            ->willReturn($betterReflectionAttributes);

        $reflectionPropertyAdapter = new ReflectionPropertyAdapter($betterReflectionProperty);
        $attributes                = $reflectionPropertyAdapter->getAttributes($someAttributeClassName);

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

        $betterReflectionProperty = self::getStubBuilder(BetterReflectionProperty::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAttributes'])
            ->getStub();

        $betterReflectionProperty
            ->method('getAttributes')
            ->willReturn($betterReflectionAttributes);

        $reflectionPropertyAdapter = new ReflectionPropertyAdapter($betterReflectionProperty);

        self::assertCount(1, $reflectionPropertyAdapter->getAttributes($className, ReflectionAttributeAdapter::IS_INSTANCEOF));
        self::assertCount(2, $reflectionPropertyAdapter->getAttributes($parentClassName, ReflectionAttributeAdapter::IS_INSTANCEOF));
        self::assertCount(2, $reflectionPropertyAdapter->getAttributes($interfaceName, ReflectionAttributeAdapter::IS_INSTANCEOF));
    }

    public function testGetAttributesThrowsExceptionForInvalidFlags(): void
    {
        $betterReflectionProperty  = self::createStub(BetterReflectionProperty::class);
        $reflectionPropertyAdapter = new ReflectionPropertyAdapter($betterReflectionProperty);

        $this->expectException(ValueError::class);
        $reflectionPropertyAdapter->getAttributes(null, 123);
    }

    public function testPropertyName(): void
    {
        $betterReflectionProperty = self::createStub(BetterReflectionProperty::class);
        $betterReflectionProperty
            ->method('getName')
            ->willReturn('foo');

        $reflectionPropertyAdapter = new ReflectionPropertyAdapter($betterReflectionProperty);
        self::assertSame('foo', $reflectionPropertyAdapter->name);
    }

    public function testPropertyClass(): void
    {
        $betterReflectionClass = self::createStub(BetterReflectionClass::class);
        $betterReflectionClass
            ->method('getName')
            ->willReturn('Foo');

        $betterReflectionProperty = self::createStub(BetterReflectionProperty::class);
        $betterReflectionProperty
            ->method('getImplementingClass')
            ->willReturn($betterReflectionClass);

        $reflectionPropertyAdapter = new ReflectionPropertyAdapter($betterReflectionProperty);
        self::assertSame('Foo', $reflectionPropertyAdapter->class);
    }

    public function testUnknownProperty(): void
    {
        $betterReflectionProperty  = self::createStub(BetterReflectionProperty::class);
        $reflectionPropertyAdapter = new ReflectionPropertyAdapter($betterReflectionProperty);
        $this->expectException(OutOfBoundsException::class);
        $this->expectExceptionMessage('Property Roave\BetterReflection\Reflection\Adapter\ReflectionProperty::$foo does not exist.');
        /** @phpstan-ignore property.notFound, expr.resultUnused */
        $reflectionPropertyAdapter->foo;
    }

    public function testSetRawValueWithoutLazyInitialization(): void
    {
        self::expectException(NotImplementedBecauseItTriggersAutoloading::class);
        self::expectExceptionMessage('Not implemented because it triggers autoloading');

        $betterReflectionProperty  = self::createStub(BetterReflectionProperty::class);
        $reflectionPropertyAdapter = new ReflectionPropertyAdapter($betterReflectionProperty);
        $reflectionPropertyAdapter->setRawValueWithoutLazyInitialization(new stdClass(), null);
    }

    public function testIsLazy(): void
    {
        self::expectException(NotImplementedBecauseItTriggersAutoloading::class);
        self::expectExceptionMessage('Not implemented because it triggers autoloading');

        $betterReflectionProperty  = self::createStub(BetterReflectionProperty::class);
        $reflectionPropertyAdapter = new ReflectionPropertyAdapter($betterReflectionProperty);
        $reflectionPropertyAdapter->isLazy(new stdClass());
    }

    public function testSkipLazyInitialization(): void
    {
        self::expectException(NotImplementedBecauseItTriggersAutoloading::class);
        self::expectExceptionMessage('Not implemented because it triggers autoloading');

        $betterReflectionProperty  = self::createStub(BetterReflectionProperty::class);
        $reflectionPropertyAdapter = new ReflectionPropertyAdapter($betterReflectionProperty);
        $reflectionPropertyAdapter->skipLazyInitialization(new stdClass());
    }

    #[RequiresPhp('>=8.4.0')]
    public function testHasAndGetHookWhenNoHooks(): void
    {
        $betterReflectionProperty = self::createStub(BetterReflectionProperty::class);
        $betterReflectionProperty
            ->method('hasHook')
            ->willReturn(false);
        $betterReflectionProperty
            ->method('getHook')
            ->willReturn(null);

        $reflectionPropertyAdapter = new ReflectionPropertyAdapter($betterReflectionProperty);
        self::assertFalse($reflectionPropertyAdapter->hasHook(PropertyHookType::Get));
        self::assertNull($reflectionPropertyAdapter->getHook(PropertyHookType::Get));
    }

    #[RequiresPhp('>=8.4.0')]
    public function testHasAndGetHook(): void
    {
        $betterReflectionProperty = self::createStub(BetterReflectionProperty::class);
        $betterReflectionProperty
            ->method('hasHook')
            ->willReturn(true);
        $betterReflectionProperty
            ->method('getHook')
            ->willReturn(self::createStub(BetterReflectionMethod::class));

        $reflectionPropertyAdapter = new ReflectionPropertyAdapter($betterReflectionProperty);
        self::assertTrue($reflectionPropertyAdapter->hasHook(PropertyHookType::Get));
        self::assertNotNull($reflectionPropertyAdapter->getHook(PropertyHookType::Get));
    }

    public function testGetSettableType(): void
    {
        $setHookParameterType = self::createStub(BetterReflectionNamedType::class);
        $setHookParameterType
            ->method('getName')
            ->willReturn('int');

        $setHookParameter = self::createStub(BetterReflectionParameter::class);
        $setHookParameter
            ->method('getType')
            ->willReturn($setHookParameterType);

        $setHook = self::createStub(BetterReflectionMethod::class);
        $setHook
            ->method('getParameters')
            ->willReturn([$setHookParameter]);

        $betterReflectionProperty = self::createStub(BetterReflectionProperty::class);
        $betterReflectionProperty
            ->method('getHook')
            ->willReturn($setHook);

        $reflectionPropertyAdapter = new ReflectionPropertyAdapter($betterReflectionProperty);
        self::assertInstanceOf(ReflectionNamedTypeAdapter::class, $reflectionPropertyAdapter->getSettableType());
        self::assertSame('int', $reflectionPropertyAdapter->getSettableType()->getName());
    }

    public function testGetSettableTypeWhenVirtual(): void
    {
        $betterReflectionProperty = self::createStub(BetterReflectionProperty::class);
        $betterReflectionProperty
            ->method('getHook')
            ->willReturn(null);
        $betterReflectionProperty
            ->method('isVirtual')
            ->willReturn(true);

        $reflectionPropertyAdapter = new ReflectionPropertyAdapter($betterReflectionProperty);
        self::assertInstanceOf(ReflectionNamedTypeAdapter::class, $reflectionPropertyAdapter->getSettableType());
        self::assertSame('never', $reflectionPropertyAdapter->getSettableType()->getName());
    }

    public function testGetSettableTypeWhenNoHooks(): void
    {
        $betterReflectionProperty = self::createStub(BetterReflectionProperty::class);
        $betterReflectionProperty
            ->method('getHook')
            ->willReturn(null);
        $betterReflectionProperty
            ->method('isVirtual')
            ->willReturn(false);
        $betterReflectionProperty
            ->method('getType')
            ->willReturn(self::createStub(BetterReflectionNamedType::class));

        $reflectionPropertyAdapter = new ReflectionPropertyAdapter($betterReflectionProperty);
        self::assertInstanceOf(ReflectionNamedTypeAdapter::class, $reflectionPropertyAdapter->getSettableType());
    }

    public function testGetSettableTypeWhenNoHooksAndNoType(): void
    {
        $betterReflectionProperty = self::createStub(BetterReflectionProperty::class);
        $betterReflectionProperty
            ->method('getHook')
            ->willReturn(null);
        $betterReflectionProperty
            ->method('isVirtual')
            ->willReturn(false);
        $betterReflectionProperty
            ->method('getType')
            ->willReturn(null);

        $reflectionPropertyAdapter = new ReflectionPropertyAdapter($betterReflectionProperty);
        self::assertNull($reflectionPropertyAdapter->getSettableType());
    }

    public function testSetRawValueWhenNoHooks(): void
    {
        self::expectException(NotImplementedBecauseItTriggersAutoloading::class);
        self::expectExceptionMessage('Not implemented because it triggers autoloading');

        $betterReflectionProperty = self::createStub(BetterReflectionProperty::class);
        $betterReflectionProperty
            ->method('hasHooks')
            ->willReturn(true);

        $reflectionPropertyAdapter = new ReflectionPropertyAdapter($betterReflectionProperty);
        $reflectionPropertyAdapter->setRawValue(new stdClass(), null);
    }

    public function testGetRawValue(): void
    {
        self::expectException(NotImplementedBecauseItTriggersAutoloading::class);
        self::expectExceptionMessage('Not implemented because it triggers autoloading');

        $betterReflectionProperty  = self::createStub(BetterReflectionProperty::class);
        $reflectionPropertyAdapter = new ReflectionPropertyAdapter($betterReflectionProperty);
        $reflectionPropertyAdapter->getRawValue(new stdClass());
    }

    public function testGetMangledNameForPublicProperty(): void
    {
        $betterReflectionClass = self::createStub(BetterReflectionClass::class);
        $betterReflectionClass
            ->method('getName')
            ->willReturn('Foo');

        $betterReflectionProperty = self::createStub(BetterReflectionProperty::class);
        $betterReflectionProperty
            ->method('getDeclaringClass')
            ->willReturn($betterReflectionClass);
        $betterReflectionProperty
            ->method('getName')
            ->willReturn('publicProperty');
        $betterReflectionProperty
            ->method('isPrivate')
            ->willReturn(false);
        $betterReflectionProperty
            ->method('isProtected')
            ->willReturn(false);

        $reflectionPropertyAdapter = new ReflectionPropertyAdapter($betterReflectionProperty);
        self::assertSame('publicProperty', $reflectionPropertyAdapter->getMangledName());
    }

    public function testGetMangledNameForProtectedProperty(): void
    {
        $betterReflectionClass = self::createStub(BetterReflectionClass::class);
        $betterReflectionClass
            ->method('getName')
            ->willReturn('Foo');

        $betterReflectionProperty = self::createStub(BetterReflectionProperty::class);
        $betterReflectionProperty
            ->method('getDeclaringClass')
            ->willReturn($betterReflectionClass);
        $betterReflectionProperty
            ->method('getName')
            ->willReturn('protectedProperty');
        $betterReflectionProperty
            ->method('isPrivate')
            ->willReturn(false);
        $betterReflectionProperty
            ->method('isProtected')
            ->willReturn(true);

        $reflectionPropertyAdapter = new ReflectionPropertyAdapter($betterReflectionProperty);
        self::assertSame("\0*\0protectedProperty", $reflectionPropertyAdapter->getMangledName());
    }

    public function testGetMangledNameForPrivateProperty(): void
    {
        $betterReflectionClass = self::createStub(BetterReflectionClass::class);
        $betterReflectionClass
            ->method('getName')
            ->willReturn('Foo');

        $betterReflectionProperty = self::createStub(BetterReflectionProperty::class);
        $betterReflectionProperty
            ->method('getDeclaringClass')
            ->willReturn($betterReflectionClass);
        $betterReflectionProperty
            ->method('getName')
            ->willReturn('privateProperty');
        $betterReflectionProperty
            ->method('isPrivate')
            ->willReturn(true);
        $betterReflectionProperty
            ->method('isProtected')
            ->willReturn(false);

        $reflectionPropertyAdapter = new ReflectionPropertyAdapter($betterReflectionProperty);
        self::assertSame("\0Foo\0privateProperty", $reflectionPropertyAdapter->getMangledName());
    }
}
