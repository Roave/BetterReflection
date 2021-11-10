<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection\Adapter;

use PHPUnit\Framework\TestCase;
use ReflectionClass as CoreReflectionClass;
use ReflectionException as CoreReflectionException;
use ReflectionObject as CoreReflectionObject;
use ReflectionProperty as CoreReflectionProperty;
use Roave\BetterReflection\Reflection\Adapter\Exception\NotImplemented;
use Roave\BetterReflection\Reflection\Adapter\ReflectionAttribute as ReflectionAttributeAdapter;
use Roave\BetterReflection\Reflection\Adapter\ReflectionClass as ReflectionClassAdapter;
use Roave\BetterReflection\Reflection\Adapter\ReflectionClassConstant as ReflectionClassConstantAdapter;
use Roave\BetterReflection\Reflection\Adapter\ReflectionMethod as ReflectionMethodAdapter;
use Roave\BetterReflection\Reflection\Adapter\ReflectionObject as ReflectionObjectAdapter;
use Roave\BetterReflection\Reflection\Adapter\ReflectionProperty as ReflectionPropertyAdapter;
use Roave\BetterReflection\Reflection\ReflectionAttribute as BetterReflectionAttribute;
use Roave\BetterReflection\Reflection\ReflectionClass as BetterReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionClassConstant as BetterReflectionClassConstant;
use Roave\BetterReflection\Reflection\ReflectionMethod as BetterReflectionMethod;
use Roave\BetterReflection\Reflection\ReflectionObject as BetterReflectionObject;
use Roave\BetterReflection\Reflection\ReflectionProperty as BetterReflectionProperty;
use Roave\BetterReflection\Util\FileHelper;
use stdClass;
use ValueError;

use function array_combine;
use function array_map;
use function get_class_methods;
use function is_array;

/**
 * @covers \Roave\BetterReflection\Reflection\Adapter\ReflectionObject
 */
class ReflectionObjectTest extends TestCase
{
    public function coreReflectionMethodNamesProvider(): array
    {
        $methods = get_class_methods(CoreReflectionObject::class);

        return array_combine($methods, array_map(static fn (string $i): array => [$i], $methods));
    }

    /**
     * @dataProvider coreReflectionMethodNamesProvider
     */
    public function testCoreReflectionMethods(string $methodName): void
    {
        $reflectionObjectAdapterReflection = new CoreReflectionClass(ReflectionObjectAdapter::class);

        self::assertTrue($reflectionObjectAdapterReflection->hasMethod($methodName));
        self::assertSame(ReflectionObjectAdapter::class, $reflectionObjectAdapterReflection->getMethod($methodName)->getDeclaringClass()->getName());
    }

    public function methodExpectationProvider(): array
    {
        $mockMethod = $this->createMock(BetterReflectionMethod::class);

        $mockProperty = $this->createMock(BetterReflectionProperty::class);

        $mockClassLike = $this->createMock(BetterReflectionClass::class);

        $mockConstant = $this->createMock(BetterReflectionClassConstant::class);

        $mockAttribute = $this->createMock(BetterReflectionAttribute::class);

        return [
            ['__toString', [], 'string', null, 'string', null],
            ['getName', [], 'name', null, 'name', null],
            ['isAnonymous', [], true, null, true, null],
            ['isInternal', [], true, null, true, null],
            ['isUserDefined', [], true, null, true, null],
            ['isInstantiable', [], true, null, true, null],
            ['isCloneable', [], true, null, true, null],
            ['getFileName', [], 'filename', null, 'filename', null],
            ['getStartLine', [], 123, null, 123, null],
            ['getEndLine', [], 123, null, 123, null],
            ['getDocComment', [], '', null, false, null],
            ['getConstructor', [], $mockMethod, null, null, null],
            ['hasMethod', ['foo'], true, null, true, null],
            ['getMethod', ['foo'], $mockMethod, null, null, ReflectionMethodAdapter::class],
            ['getMethods', [], [$mockMethod], null, null, ReflectionMethodAdapter::class],
            ['hasProperty', ['foo'], true, null, true, null],
            ['getProperty', ['foo'], $mockProperty, null, null, ReflectionPropertyAdapter::class],
            ['getProperties', [], [$mockProperty], null, null, ReflectionPropertyAdapter::class],
            ['hasConstant', ['foo'], true, null, true, null],
            ['getConstant', ['foo'], 'a', null, 'a', null],
            ['getReflectionConstant', ['foo'], $mockConstant, null, null, ReflectionClassConstantAdapter::class],
            ['getReflectionConstants', [], [$mockConstant], null, null, ReflectionClassConstantAdapter::class],
            ['getInterfaces', [], [$mockClassLike], null, null, ReflectionClassAdapter::class],
            ['getInterfaceNames', [], ['a', 'b'], null, ['a', 'b'], null],
            ['isInterface', [], true, null, true, null],
            ['getTraits', [], [$mockClassLike], null, null, ReflectionClassAdapter::class],
            ['getTraitNames', [], ['a', 'b'], null, ['a', 'b'], null],
            ['getTraitAliases', [], ['a', 'b'], null, ['a', 'b'], null],
            ['isTrait', [], true, null, true, null],
            ['isAbstract', [], true, null, true, null],
            ['isFinal', [], true, null, true, null],
            ['getModifiers', [], 123, null, 123, null],
            ['isInstance', [new stdClass()], true, null, true, null],
            ['newInstance', [], null, NotImplemented::class, null, null],
            ['newInstanceWithoutConstructor', [], null, NotImplemented::class, null, null],
            ['newInstanceArgs', [], null, NotImplemented::class, null, null],
            ['getParentClass', [], $mockClassLike, null, null, ReflectionClassAdapter::class],
            ['isSubclassOf', ['\stdClass'], true, null, true, null],
            ['getStaticProperties', [], [], null, [], null],
            ['getDefaultProperties', [], ['foo' => 'bar'], null, null, null],
            ['isIterateable', [], true, null, true, null],
            ['implementsInterface', ['\Traversable'], true, null, true, null],
            ['getExtension', [], null, NotImplemented::class, null, null],
            ['getExtensionName', [], null, null, false, null],
            ['inNamespace', [], true, null, true, null],
            ['getNamespaceName', [], '', null, '', null],
            ['getShortName', [], 'shortName', null, 'shortName', null],
            ['getAttributes', [], [$mockAttribute], null, null, ReflectionAttributeAdapter::class],
            ['isEnum', [], true, null, true, null],
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
        ?string $expectedException,
        mixed $expectedReturnValue,
        ?string $expectedReturnValueInstance,
    ): void {
        $reflectionStub = $this->createMock(BetterReflectionObject::class);

        if ($expectedException === null) {
            $reflectionStub->expects($this->once())
                ->method($methodName)
                ->with(...$args)
                ->willReturn($returnValue);
        }

        $adapter = new ReflectionObjectAdapter($reflectionStub);

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

    public function testGetFileNameReturnsFalseWhenNoFileName(): void
    {
        $betterReflectionObject = $this->createMock(BetterReflectionObject::class);
        $betterReflectionObject
            ->method('getFileName')
            ->willReturn(null);

        $betterReflectionObject = new ReflectionObjectAdapter($betterReflectionObject);

        self::assertFalse($betterReflectionObject->getFileName());
    }

    public function testGetFileNameReturnsPathWithSystemDirectorySeparator(): void
    {
        $fileName = 'foo/bar\\foo/bar.php';

        $betterReflectionObject = $this->createMock(BetterReflectionObject::class);
        $betterReflectionObject
            ->method('getFileName')
            ->willReturn($fileName);

        $betterReflectionObject = new ReflectionObjectAdapter($betterReflectionObject);

        self::assertSame(FileHelper::normalizeSystemPath($fileName), $betterReflectionObject->getFileName());
    }

    public function testGetDocCommentReturnsFalseWhenNoDocComment(): void
    {
        $betterReflectionObject = $this->createMock(BetterReflectionObject::class);
        $betterReflectionObject
            ->method('getDocComment')
            ->willReturn('');

        $reflectionObjectAdapter = new ReflectionObjectAdapter($betterReflectionObject);

        self::assertFalse($reflectionObjectAdapter->getDocComment());
    }

    public function testGetParentObjectReturnsFalseWhenNoParent(): void
    {
        $betterReflectionObject = $this->createMock(BetterReflectionObject::class);
        $betterReflectionObject
            ->method('getParentClass')
            ->willReturn(null);

        $reflectionObjectAdapter = new ReflectionObjectAdapter($betterReflectionObject);

        self::assertFalse($reflectionObjectAdapter->getParentClass());
    }

    public function testHasMethodIsCaseInsensitive(): void
    {
        $betterReflectionMethod = $this->createMock(BetterReflectionMethod::class);
        $betterReflectionMethod
            ->method('getName')
            ->willReturn('fooBoo');

        $betterReflectionObject = $this->createMock(BetterReflectionObject::class);
        $betterReflectionObject
            ->method('getMethods')
            ->willReturn([$betterReflectionMethod]);
        $betterReflectionObject
            ->method('hasMethod')
            ->with('fooBoo')
            ->willReturn(true);

        $reflectionObjectAdapter = new ReflectionObjectAdapter($betterReflectionObject);

        self::assertTrue($reflectionObjectAdapter->hasMethod('fooBoo'));
        self::assertTrue($reflectionObjectAdapter->hasMethod('fooboo'));
        self::assertTrue($reflectionObjectAdapter->hasMethod('fOObOO'));
    }

    public function testGetMethodIsCaseInsensitive(): void
    {
        $betterReflectionMethod = $this->createMock(BetterReflectionMethod::class);
        $betterReflectionMethod
            ->method('getName')
            ->willReturn('fooBoo');

        $betterReflectionObject = $this->createMock(BetterReflectionObject::class);
        $betterReflectionObject
            ->method('getMethods')
            ->willReturn([$betterReflectionMethod]);
        $betterReflectionObject
            ->method('getMethod')
            ->with('fooBoo')
            ->willReturn($betterReflectionMethod);

        $reflectionObjectAdapter = new ReflectionObjectAdapter($betterReflectionObject);

        self::assertSame('fooBoo', $reflectionObjectAdapter->getMethod('fooBoo')->getName());
        self::assertSame('fooBoo', $reflectionObjectAdapter->getMethod('fooboo')->getName());
        self::assertSame('fooBoo', $reflectionObjectAdapter->getMethod('fOObOO')->getName());
    }

    public function testIsSubclassOfWithObject(): void
    {
        $betterReflectionObject = $this->createMock(BetterReflectionObject::class);
        $betterReflectionObject
            ->method('getParentClassNames')
            ->willReturn(['Foo']);
        $betterReflectionObject
            ->method('isSubclassOf')
            ->with('Foo')
            ->willReturn(true);

        $reflectionObjectAdapter = new ReflectionObjectAdapter($betterReflectionObject);

        $fooClassMock = $this->createMock(CoreReflectionClass::class);
        $fooClassMock
            ->method('getName')
            ->willReturn('Foo');

        self::assertTrue($reflectionObjectAdapter->isSubclassOf($fooClassMock));
    }

    public function testIsSubclassOfIsCaseInsensitive(): void
    {
        $betterReflectionObject = $this->createMock(BetterReflectionObject::class);
        $betterReflectionObject
            ->method('getParentClassNames')
            ->willReturn(['Foo']);
        $betterReflectionObject
            ->method('isSubclassOf')
            ->with('Foo')
            ->willReturn(true);

        $reflectionObjectAdapter = new ReflectionObjectAdapter($betterReflectionObject);

        self::assertTrue($reflectionObjectAdapter->isSubclassOf('Foo'));
        self::assertTrue($reflectionObjectAdapter->isSubclassOf('foo'));
        self::assertTrue($reflectionObjectAdapter->isSubclassOf('FoO'));
    }

    public function testImplementsInterfaceWithObject(): void
    {
        $betterReflectionObject = $this->createMock(BetterReflectionObject::class);
        $betterReflectionObject
            ->method('getInterfaceNames')
            ->willReturn(['Foo']);
        $betterReflectionObject
            ->method('implementsInterface')
            ->with('Foo')
            ->willReturn(true);

        $reflectionObjectAdapter = new ReflectionObjectAdapter($betterReflectionObject);

        $fooClassMock = $this->createMock(CoreReflectionClass::class);
        $fooClassMock
            ->method('getName')
            ->willReturn('Foo');

        self::assertTrue($reflectionObjectAdapter->implementsInterface($fooClassMock));
    }

    public function testImplementsInterfaceIsCaseInsensitive(): void
    {
        $betterReflectionObject = $this->createMock(BetterReflectionObject::class);
        $betterReflectionObject
            ->method('getInterfaceNames')
            ->willReturn(['Foo']);
        $betterReflectionObject
            ->method('implementsInterface')
            ->with('Foo')
            ->willReturn(true);

        $reflectionObjectAdapter = new ReflectionObjectAdapter($betterReflectionObject);

        self::assertTrue($reflectionObjectAdapter->implementsInterface('Foo'));
        self::assertTrue($reflectionObjectAdapter->implementsInterface('foo'));
        self::assertTrue($reflectionObjectAdapter->implementsInterface('FoO'));
    }

    public function testGetPropertyThrowsExceptionWhenPropertyDoesNotExist(): void
    {
        $betterReflectionObject = $this->createMock(BetterReflectionObject::class);
        $betterReflectionObject
            ->method('getProperty')
            ->with('foo')
            ->willReturn(null);

        $reflectionObjectAdapter = new ReflectionObjectAdapter($betterReflectionObject);

        $this->expectException(CoreReflectionException::class);
        $reflectionObjectAdapter->getProperty('foo');
    }

    public function testGetStaticPropertyValue(): void
    {
        $betterReflectionProperty = $this->createMock(BetterReflectionProperty::class);
        $betterReflectionProperty
            ->method('isPublic')
            ->willReturn(true);
        $betterReflectionProperty
            ->method('isStatic')
            ->willReturn(true);
        $betterReflectionProperty
            ->method('getValue')
            ->willReturn(123);

        $betterReflectionObject = $this->createMock(BetterReflectionObject::class);
        $betterReflectionObject
            ->method('getProperty')
            ->with('foo')
            ->willReturn($betterReflectionProperty);

        $reflectionClassAdapter = new ReflectionObjectAdapter($betterReflectionObject);

        self::assertSame(123, $reflectionClassAdapter->getStaticPropertyValue('foo'));
    }

    public function testSetStaticPropertyValue(): void
    {
        $betterReflectionProperty = $this->createMock(BetterReflectionProperty::class);
        $betterReflectionProperty
            ->method('isPublic')
            ->willReturn(true);
        $betterReflectionProperty
            ->method('isStatic')
            ->willReturn(true);
        $betterReflectionProperty
            ->expects($this->once())
            ->method('setValue')
            ->with(123);

        $betterReflectionObject = $this->createMock(BetterReflectionObject::class);
        $betterReflectionObject
            ->method('getProperty')
            ->with('foo')
            ->willReturn($betterReflectionProperty);

        $reflectionClassAdapter = new ReflectionObjectAdapter($betterReflectionObject);

        $reflectionClassAdapter->setStaticPropertyValue('foo', 123);
    }

    public function testGetStaticPropertyValueThrowsExceptionWhenPropertyNotAccessible(): void
    {
        $betterReflectionProperty = $this->createMock(BetterReflectionProperty::class);
        $betterReflectionProperty
            ->method('isPublic')
            ->willReturn(false);

        $betterReflectionObject = $this->createMock(BetterReflectionObject::class);
        $betterReflectionObject
            ->method('getProperty')
            ->with('foo')
            ->willReturn($betterReflectionProperty);

        $reflectionObjectAdapter = new ReflectionObjectAdapter($betterReflectionObject);

        $this->expectException(CoreReflectionException::class);
        $this->expectExceptionMessage('Property "foo" is not accessible');
        $reflectionObjectAdapter->getStaticPropertyValue('foo');
    }

    public function testSetStaticPropertyValueThrowsExceptionWhenPropertyNotAccessible(): void
    {
        $betterReflectionProperty = $this->createMock(BetterReflectionProperty::class);
        $betterReflectionProperty
            ->method('isPublic')
            ->willReturn(false);

        $betterReflectionObject = $this->createMock(BetterReflectionObject::class);
        $betterReflectionObject
            ->method('getProperty')
            ->with('foo')
            ->willReturn($betterReflectionProperty);

        $reflectionObjectAdapter = new ReflectionObjectAdapter($betterReflectionObject);

        $this->expectException(CoreReflectionException::class);
        $this->expectExceptionMessage('Property "foo" is not accessible');
        $reflectionObjectAdapter->setStaticPropertyValue('foo', null);
    }

    public function testGetStaticPropertyValueThrowsExceptionWhenPropertyDoesNotExist(): void
    {
        $betterReflectionObject = $this->createMock(BetterReflectionObject::class);
        $betterReflectionObject
            ->method('getProperty')
            ->willReturn(null);

        $reflectionObjectAdapter = new ReflectionObjectAdapter($betterReflectionObject);

        $this->expectException(CoreReflectionException::class);
        $this->expectExceptionMessage('Property "foo" does not exist');
        $reflectionObjectAdapter->getStaticPropertyValue('foo');
    }

    public function testGetStaticPropertyValueReturnsDefaultValueWhenPropertyDoesNotExist(): void
    {
        $betterReflectionObject = $this->createMock(BetterReflectionObject::class);
        $betterReflectionObject
            ->method('getProperty')
            ->willReturn(null);

        $reflectionObjectAdapter = new ReflectionObjectAdapter($betterReflectionObject);

        self::assertSame('default', $reflectionObjectAdapter->getStaticPropertyValue('foo', 'default'));
    }

    public function testSetStaticPropertyValueThrowsExceptionWhenPropertyDoesNotExist(): void
    {
        $betterReflectionObject = $this->createMock(BetterReflectionObject::class);
        $betterReflectionObject
            ->method('getProperty')
            ->willReturn(null);

        $reflectionObjectAdapter = new ReflectionObjectAdapter($betterReflectionObject);

        $this->expectException(CoreReflectionException::class);
        $this->expectExceptionMessage('Property "foo" does not exist');
        $reflectionObjectAdapter->setStaticPropertyValue('foo', null);
    }

    public function testGetStaticPropertyValueThrowsExceptionWhenPropertyIsNotStatic(): void
    {
        $betterReflectionProperty = $this->createMock(BetterReflectionProperty::class);
        $betterReflectionProperty
            ->method('isPublic')
            ->willReturn(true);
        $betterReflectionProperty
            ->method('isStatic')
            ->willReturn(false);

        $betterReflectionObject = $this->createMock(BetterReflectionObject::class);
        $betterReflectionObject
            ->method('getProperty')
            ->with('foo')
            ->willReturn($betterReflectionProperty);

        $reflectionObjectAdapter = new ReflectionObjectAdapter($betterReflectionObject);

        $this->expectException(CoreReflectionException::class);
        $this->expectExceptionMessage('Property "foo" is not static');
        $reflectionObjectAdapter->getStaticPropertyValue('foo');
    }

    public function testSetStaticPropertyValueThrowsExceptionWhenPropertyIsNotStatic(): void
    {
        $betterReflectionProperty = $this->createMock(BetterReflectionProperty::class);
        $betterReflectionProperty
            ->method('isPublic')
            ->willReturn(true);
        $betterReflectionProperty
            ->method('isStatic')
            ->willReturn(false);

        $betterReflectionObject = $this->createMock(BetterReflectionObject::class);
        $betterReflectionObject
            ->method('getProperty')
            ->with('foo')
            ->willReturn($betterReflectionProperty);

        $reflectionObjectAdapter = new ReflectionObjectAdapter($betterReflectionObject);

        $this->expectException(CoreReflectionException::class);
        $this->expectExceptionMessage('Property "foo" is not static');
        $reflectionObjectAdapter->setStaticPropertyValue('foo', null);
    }

    public function testIsIterable(): void
    {
        $betterReflectionObject = $this->createMock(BetterReflectionObject::class);
        $betterReflectionObject
            ->method('isIterateable')
            ->willReturn(true);

        $reflectionObjectAdapter = new ReflectionObjectAdapter($betterReflectionObject);

        self::assertTrue($reflectionObjectAdapter->isIterable());
    }

    public function testGetExtensionNameReturnsEmptyStringWhenNoExtensionName(): void
    {
        $betterReflectionObject = $this->createMock(BetterReflectionObject::class);
        $betterReflectionObject
            ->method('getExtensionName')
            ->willReturn('');

        $betterReflectionObject = new ReflectionObjectAdapter($betterReflectionObject);

        self::assertSame('', $betterReflectionObject->getExtensionName());
    }

    public function testGetConstantsWithFilter(): void
    {
        $betterReflectionObject                 = $this->createMock(BetterReflectionObject::class);
        $publicBetterReflectionClassConstant    = $this->createMock(BetterReflectionClassConstant::class);
        $privateBetterReflectionClassConstant   = $this->createMock(BetterReflectionClassConstant::class);
        $protectedBetterReflectionClassConstant = $this->createMock(BetterReflectionClassConstant::class);

        $publicBetterReflectionClassConstant
            ->method('getModifiers')
            ->willReturn(CoreReflectionProperty::IS_PUBLIC);

        $publicBetterReflectionClassConstant
            ->method('getName')
            ->willReturn('PUBLIC_CONSTANT');

        $publicBetterReflectionClassConstant
            ->method('getValue')
            ->willReturn('public constant');

        $privateBetterReflectionClassConstant
            ->method('getModifiers')
            ->willReturn(CoreReflectionProperty::IS_PRIVATE);

        $privateBetterReflectionClassConstant
            ->method('getName')
            ->willReturn('PRIVATE_CONSTANT');

        $privateBetterReflectionClassConstant
            ->method('getValue')
            ->willReturn('private constant');

        $protectedBetterReflectionClassConstant
            ->method('getModifiers')
            ->willReturn(CoreReflectionProperty::IS_PROTECTED);

        $protectedBetterReflectionClassConstant
            ->method('getName')
            ->willReturn('PROTECTED_CONSTANT');

        $protectedBetterReflectionClassConstant
            ->method('getValue')
            ->willReturn('protected constant');

        $betterReflectionObject
            ->method('getReflectionConstants')
            ->willReturn([
                $publicBetterReflectionClassConstant->getName() => $publicBetterReflectionClassConstant,
                $privateBetterReflectionClassConstant->getName() => $privateBetterReflectionClassConstant,
                $protectedBetterReflectionClassConstant->getName() => $protectedBetterReflectionClassConstant,
            ]);

        $reflectionObject = new ReflectionObjectAdapter($betterReflectionObject);

        $allConstants       = $reflectionObject->getConstants();
        $publicConstants    = $reflectionObject->getConstants(CoreReflectionProperty::IS_PUBLIC);
        $privateConstants   = $reflectionObject->getConstants(CoreReflectionProperty::IS_PRIVATE);
        $protectedConstants = $reflectionObject->getConstants(CoreReflectionProperty::IS_PROTECTED);

        self::assertCount(3, $allConstants);

        self::assertCount(1, $publicConstants);
        self::assertEquals([$publicBetterReflectionClassConstant->getName() => $publicBetterReflectionClassConstant->getValue()], $publicConstants);

        self::assertCount(1, $privateConstants);
        self::assertEquals([$privateBetterReflectionClassConstant->getName() => $privateBetterReflectionClassConstant->getValue()], $privateConstants);

        self::assertCount(1, $protectedConstants);
        self::assertEquals([$protectedBetterReflectionClassConstant->getName() => $protectedBetterReflectionClassConstant->getValue()], $protectedConstants);
    }

    public function testGetReflectionConstantReturnsFalseWhenConstantDoesNotExist(): void
    {
        $betterReflectionObject = $this->createMock(BetterReflectionObject::class);

        $betterReflectionObject
            ->expects($this->once())
            ->method('getReflectionConstant')
            ->with('NON_EXISTENT_CONSTANT')
            ->willReturn(null);

        $reflectionObjectAdapter = new ReflectionObjectAdapter($betterReflectionObject);

        self::assertFalse($reflectionObjectAdapter->getReflectionConstant('NON_EXISTENT_CONSTANT'));
    }

    public function testGetReflectionConstantReturnsClassConstantAdapterWhenConstantExists(): void
    {
        $betterReflectionObject        = $this->createMock(BetterReflectionObject::class);
        $betterReflectionClassConstant = $this->createMock(BetterReflectionClassConstant::class);

        $betterReflectionObject
            ->expects($this->once())
            ->method('getReflectionConstant')
            ->with('SOME_CONSTANT')
            ->willReturn($betterReflectionClassConstant);

        $reflectionObjectAdapter = new ReflectionObjectAdapter($betterReflectionObject);

        self::assertInstanceOf(ReflectionClassConstantAdapter::class, $reflectionObjectAdapter->getReflectionConstant('SOME_CONSTANT'));
    }

    public function testGetReflectionConstantsReturnsClassConstantAdapter(): void
    {
        $betterReflectionObject        = $this->createMock(BetterReflectionObject::class);
        $betterReflectionClassConstant = $this->createMock(BetterReflectionClassConstant::class);

        $betterReflectionObject
            ->expects($this->once())
            ->method('getReflectionConstants')
            ->willReturn([$betterReflectionClassConstant]);

        $reflectionObjectAdapter = new ReflectionObjectAdapter($betterReflectionObject);

        self::assertContainsOnlyInstancesOf(ReflectionClassConstantAdapter::class, $reflectionObjectAdapter->getReflectionConstants());
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

        $betterReflectionObject = $this->createMock(BetterReflectionObject::class);
        $betterReflectionObject
            ->method('getAttributes')
            ->willReturn($betterReflectionAttributes);

        $reflectionObjectAdapter = new ReflectionObjectAdapter($betterReflectionObject);
        $attributes              = $reflectionObjectAdapter->getAttributes();

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

        $betterReflectionClass = $this->getMockBuilder(BetterReflectionClass::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAttributes'])
            ->getMock();

        $betterReflectionClass
            ->method('getAttributes')
            ->willReturn($betterReflectionAttributes);

        $betterReflectionObjectReflection = new CoreReflectionClass(BetterReflectionObject::class);
        $betterReflectionObject           = $betterReflectionObjectReflection->newInstanceWithoutConstructor();

        $betterReflectionObjectClassPropertyReflection = $betterReflectionObjectReflection->getProperty('reflectionClass');
        $betterReflectionObjectClassPropertyReflection->setAccessible(true);
        $betterReflectionObjectClassPropertyReflection->setValue($betterReflectionObject, $betterReflectionClass);

        $reflectionObjectAdapter = new ReflectionObjectAdapter($betterReflectionObject);

        $attributes = $reflectionObjectAdapter->getAttributes('SomeAttribute');

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

        $betterReflectionClass = $this->getMockBuilder(BetterReflectionClass::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAttributes'])
            ->getMock();

        $betterReflectionClass
            ->method('getAttributes')
            ->willReturn($betterReflectionAttributes);

        $betterReflectionObjectReflection = new CoreReflectionClass(BetterReflectionObject::class);
        $betterReflectionObject           = $betterReflectionObjectReflection->newInstanceWithoutConstructor();

        $betterReflectionObjectClassPropertyReflection = $betterReflectionObjectReflection->getProperty('reflectionClass');
        $betterReflectionObjectClassPropertyReflection->setAccessible(true);
        $betterReflectionObjectClassPropertyReflection->setValue($betterReflectionObject, $betterReflectionClass);

        $reflectionObjectAdapter = new ReflectionObjectAdapter($betterReflectionObject);

        self::assertCount(1, $reflectionObjectAdapter->getAttributes('ClassName', ReflectionAttributeAdapter::IS_INSTANCEOF));
        self::assertCount(2, $reflectionObjectAdapter->getAttributes('ParentClassName', ReflectionAttributeAdapter::IS_INSTANCEOF));
        self::assertCount(2, $reflectionObjectAdapter->getAttributes('InterfaceName', ReflectionAttributeAdapter::IS_INSTANCEOF));
    }

    public function testGetAttributesThrowsExceptionForInvalidFlags(): void
    {
        $betterReflectionObject  = $this->createMock(BetterReflectionObject::class);
        $reflectionObjectAdapter = new ReflectionObjectAdapter($betterReflectionObject);

        self::expectException(ValueError::class);
        $reflectionObjectAdapter->getAttributes(null, 123);
    }
}
