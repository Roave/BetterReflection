<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection\Adapter;

use PHPUnit\Framework\TestCase;
use ReflectionClass as CoreReflectionClass;
use ReflectionException as CoreReflectionException;
use ReflectionProperty as CoreReflectionProperty;
use Roave\BetterReflection\Reflection\Adapter\ReflectionAttribute as ReflectionAttributeAdapter;
use Roave\BetterReflection\Reflection\Adapter\ReflectionClass as ReflectionClassAdapter;
use Roave\BetterReflection\Reflection\Adapter\ReflectionProperty as ReflectionPropertyAdapter;
use Roave\BetterReflection\Reflection\Exception\NoObjectProvided;
use Roave\BetterReflection\Reflection\Exception\NotAnObject;
use Roave\BetterReflection\Reflection\Exception\ObjectNotInstanceOfClass;
use Roave\BetterReflection\Reflection\ReflectionAttribute as BetterReflectionAttribute;
use Roave\BetterReflection\Reflection\ReflectionClass as BetterReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionNamedType as BetterReflectionNamedType;
use Roave\BetterReflection\Reflection\ReflectionProperty as BetterReflectionProperty;
use stdClass;

use function array_combine;
use function array_map;
use function get_class_methods;

/**
 * @covers \Roave\BetterReflection\Reflection\Adapter\ReflectionProperty
 */
class ReflectionPropertyTest extends TestCase
{
    public function coreReflectionMethodNamesProvider(): array
    {
        $methods = get_class_methods(CoreReflectionProperty::class);

        return array_combine($methods, array_map(static fn (string $i): array => [$i], $methods));
    }

    /**
     * @dataProvider coreReflectionMethodNamesProvider
     */
    public function testCoreReflectionMethods(string $methodName): void
    {
        $reflectionPropertyAdapterReflection = new CoreReflectionClass(ReflectionPropertyAdapter::class);

        self::assertTrue($reflectionPropertyAdapterReflection->hasMethod($methodName));
        self::assertSame(ReflectionPropertyAdapter::class, $reflectionPropertyAdapterReflection->getMethod($methodName)->getDeclaringClass()->getName());
    }

    public function methodExpectationProvider(): array
    {
        $mockType = $this->createMock(BetterReflectionNamedType::class);

        return [
            ['__toString', null, '', []],
            ['getName', null, '', []],
            ['isPublic', null, true, []],
            ['isPrivate', null, true, []],
            ['isProtected', null, true, []],
            ['isStatic', null, true, []],
            ['isDefault', null, true, []],
            ['getModifiers', null, 123, []],
            ['getDocComment', null, '', []],
            ['hasType', null, true, []],
            ['getType', null, $mockType, []],
            ['hasDefaultValue', null, true, []],
            ['getDefaultValue', null, null, []],
            ['isPromoted', null, true, []],
            ['getAttributes', null, [], []],
            ['isReadOnly', null, true, []],
        ];
    }

    /**
     * @param list<mixed> $args
     *
     * @dataProvider methodExpectationProvider
     */
    public function testAdapterMethods(string $methodName, ?string $expectedException, mixed $returnValue, array $args): void
    {
        $reflectionStub = $this->createMock(BetterReflectionProperty::class);

        if ($expectedException === null) {
            $reflectionStub->expects($this->once())
                ->method($methodName)
                ->with(...$args)
                ->will($this->returnValue($returnValue));
        }

        if ($expectedException !== null) {
            $this->expectException($expectedException);
        }

        $adapter = new ReflectionPropertyAdapter($reflectionStub);
        $adapter->{$methodName}(...$args);
    }

    public function testGetDocCommentReturnsFalseWhenNoDocComment(): void
    {
        $betterReflectionProperty = $this->createMock(BetterReflectionProperty::class);
        $betterReflectionProperty
            ->method('getDocComment')
            ->willReturn('');

        $reflectionPropertyAdapter = new ReflectionPropertyAdapter($betterReflectionProperty);

        self::assertFalse($reflectionPropertyAdapter->getDocComment());
    }

    public function testGetDeclaringClass(): void
    {
        $betterReflectionClass = $this->createMock(BetterReflectionClass::class);
        $betterReflectionClass
            ->method('getName')
            ->willReturn('DeclaringClass');

        $betterReflectionProperty = $this->createMock(BetterReflectionProperty::class);
        $betterReflectionProperty
            ->method('getImplementingClass')
            ->willReturn($betterReflectionClass);

        $reflectionPropertyAdapter = new ReflectionPropertyAdapter($betterReflectionProperty);

        self::assertInstanceOf(ReflectionClassAdapter::class, $reflectionPropertyAdapter->getDeclaringClass());
        self::assertSame('DeclaringClass', $reflectionPropertyAdapter->getDeclaringClass()->getName());
    }

    public function testGetValueReturnsNullWhenNoObject(): void
    {
        $betterReflectionProperty = $this->createMock(BetterReflectionProperty::class);
        $betterReflectionProperty
            ->method('isPublic')
            ->willReturn(true);
        $betterReflectionProperty
            ->method('getValue')
            ->willThrowException(NoObjectProvided::create());

        $reflectionPropertyAdapter = new ReflectionPropertyAdapter($betterReflectionProperty);

        self::assertNull($reflectionPropertyAdapter->getValue());
    }

    public function testSetValueReturnsNullWhenNoObject(): void
    {
        $betterReflectionProperty = $this->createMock(BetterReflectionProperty::class);
        $betterReflectionProperty
            ->method('isPublic')
            ->willReturn(true);
        $betterReflectionProperty
            ->method('setValue')
            ->willThrowException(NoObjectProvided::create());

        $reflectionPropertyAdapter = new ReflectionPropertyAdapter($betterReflectionProperty);

        self::assertNull($reflectionPropertyAdapter->setValue(null));
    }

    public function testSetValueReturnsNullWhenNotAnObject(): void
    {
        $betterReflectionProperty = $this->createMock(BetterReflectionProperty::class);
        $betterReflectionProperty
            ->method('isPublic')
            ->willReturn(true);
        $betterReflectionProperty
            ->method('setValue')
            ->willThrowException(NotAnObject::fromNonObject('string'));

        $reflectionPropertyAdapter = new ReflectionPropertyAdapter($betterReflectionProperty);

        self::assertNull($reflectionPropertyAdapter->setValue('string'));
    }

    public function testGetValueThrowsExceptionWhenPropertyNotAccessible(): void
    {
        $betterReflectionProperty = $this->createMock(BetterReflectionProperty::class);
        $betterReflectionProperty
            ->method('isPublic')
            ->willReturn(false);

        $reflectionPropertyAdapter = new ReflectionPropertyAdapter($betterReflectionProperty);

        $this->expectException(CoreReflectionException::class);
        $reflectionPropertyAdapter->getValue(new stdClass());
    }

    public function testSetValueThrowsExceptionWhenPropertyNotAccessible(): void
    {
        $betterReflectionProperty = $this->createMock(BetterReflectionProperty::class);
        $betterReflectionProperty
            ->method('isPublic')
            ->willReturn(false);

        $reflectionPropertyAdapter = new ReflectionPropertyAdapter($betterReflectionProperty);

        $this->expectException(CoreReflectionException::class);
        $reflectionPropertyAdapter->setValue(new stdClass());
    }

    public function testSetAccessibleAndGetValue(): void
    {
        $betterReflectionProperty = $this->createMock(BetterReflectionProperty::class);
        $betterReflectionProperty
            ->method('isPublic')
            ->willReturn(false);
        $betterReflectionProperty
            ->method('getValue')
            ->willReturn(123);

        $reflectionPropertyAdapter = new ReflectionPropertyAdapter($betterReflectionProperty);

        $reflectionPropertyAdapter->setAccessible(true);
        self::assertSame(123, $reflectionPropertyAdapter->getValue(new stdClass()));
    }

    public function testSetAccessibleAndSetValue(): void
    {
        $betterReflectionProperty = $this->createMock(BetterReflectionProperty::class);
        $betterReflectionProperty
            ->method('isPublic')
            ->willReturn(false);
        $betterReflectionProperty
            ->expects($this->once())
            ->method('setValue')
            ->with(null, 123);

        $reflectionPropertyAdapter = new ReflectionPropertyAdapter($betterReflectionProperty);

        $reflectionPropertyAdapter->setAccessible(true);
        $reflectionPropertyAdapter->setValue(null, 123);
    }

    public function testGetValueThrowsExceptionWhenObjectNotInstanceOfClass(): void
    {
        $betterReflectionProperty = $this->createMock(BetterReflectionProperty::class);
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
        $betterReflectionProperty = $this->createMock(BetterReflectionProperty::class);
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

    public function testIsInitializedThrowsExceptionWhenNotAccessible(): void
    {
        self::expectException(CoreReflectionException::class);

        $betterReflectionProperty = $this->createMock(BetterReflectionProperty::class);
        $betterReflectionProperty
            ->method('isPublic')
            ->willReturn(false);

        $reflectionPropertyAdapter = new ReflectionPropertyAdapter($betterReflectionProperty);

        $reflectionPropertyAdapter->isInitialized(new stdClass());
    }

    public function testIsInitializedWithSetAccessible(): void
    {
        $betterReflectionProperty = $this->createMock(BetterReflectionProperty::class);
        $betterReflectionProperty
            ->method('isPublic')
            ->willReturn(false);
        $betterReflectionProperty
            ->method('isInitialized')
            ->willReturn(true);

        $reflectionPropertyAdapter = new ReflectionPropertyAdapter($betterReflectionProperty);

        $reflectionPropertyAdapter->setAccessible(true);
        self::assertTrue($reflectionPropertyAdapter->isInitialized(new stdClass()));
    }

    public function testIsInitializedThrowsExceptionWhenObjectNotInstanceOfClass(): void
    {
        $betterReflectionProperty = $this->createMock(BetterReflectionProperty::class);
        $betterReflectionProperty
            ->method('isPublic')
            ->willReturn(true);
        $betterReflectionProperty
            ->method('isInitialized')
            ->willThrowException(ObjectNotInstanceOfClass::fromClassName('Foo'));

        $reflectionPropertyAdapter = new ReflectionPropertyAdapter($betterReflectionProperty);

        self::expectException(CoreReflectionException::class);

        $reflectionPropertyAdapter->isInitialized(new stdClass());
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

        $betterReflectionProperty = $this->createMock(BetterReflectionProperty::class);
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
        $betterReflectionAttribute1 = $this->createMock(BetterReflectionAttribute::class);
        $betterReflectionAttribute1
            ->method('getName')
            ->willReturn('SomeAttribute');
        $betterReflectionAttribute2 = $this->createMock(BetterReflectionAttribute::class);
        $betterReflectionAttribute2
            ->method('getName')
            ->willReturn('AnotherAttribute');

        $betterReflectionAttributes = [$betterReflectionAttribute1, $betterReflectionAttribute2];

        $betterReflectionProperty = $this->getMockBuilder(BetterReflectionProperty::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAttributes'])
            ->getMock();

        $betterReflectionProperty
            ->method('getAttributes')
            ->willReturn($betterReflectionAttributes);

        $reflectionPropertyAdapter = new ReflectionPropertyAdapter($betterReflectionProperty);
        $attributes                = $reflectionPropertyAdapter->getAttributes('SomeAttribute');

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

        $betterReflectionProperty = $this->getMockBuilder(BetterReflectionProperty::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAttributes'])
            ->getMock();

        $betterReflectionProperty
            ->method('getAttributes')
            ->willReturn($betterReflectionAttributes);

        $reflectionPropertyAdapter = new ReflectionPropertyAdapter($betterReflectionProperty);

        self::assertCount(1, $reflectionPropertyAdapter->getAttributes('ClassName', ReflectionAttributeAdapter::IS_INSTANCEOF));
        self::assertCount(2, $reflectionPropertyAdapter->getAttributes('ParentClassName', ReflectionAttributeAdapter::IS_INSTANCEOF));
        self::assertCount(2, $reflectionPropertyAdapter->getAttributes('InterfaceName', ReflectionAttributeAdapter::IS_INSTANCEOF));
    }
}
