<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection\Adapter;

use OutOfBoundsException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass as CoreReflectionClass;
use ReflectionClassConstant as CoreReflectionClassConstant;
use ReflectionException as CoreReflectionException;
use ReflectionMethod as CoreReflectionMethod;
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

#[CoversClass(ReflectionObjectAdapter::class)]
class ReflectionObjectTest extends TestCase
{
    /** @return array<string, array{0: string}> */
    public static function coreReflectionMethodNamesProvider(): array
    {
        $methods = get_class_methods(CoreReflectionObject::class);

        return array_combine($methods, array_map(static fn (string $i): array => [$i], $methods));
    }

    #[DataProvider('coreReflectionMethodNamesProvider')]
    public function testCoreReflectionMethods(string $methodName): void
    {
        $reflectionObjectAdapterReflection = new CoreReflectionClass(ReflectionObjectAdapter::class);

        self::assertTrue($reflectionObjectAdapterReflection->hasMethod($methodName));
        self::assertSame(ReflectionObjectAdapter::class, $reflectionObjectAdapterReflection->getMethod($methodName)->getDeclaringClass()->getName());
    }

    /** @return list<array{0: string, 1: list<mixed>, 2: mixed, 3: string|null, 4: mixed}> */
    public static function methodExpectationProvider(): array
    {
        return [
            ['__toString', [], 'string', null, 'string'],
            ['getName', [], 'name', null, 'name'],
            ['isAnonymous', [], true, null, true],
            ['isInternal', [], true, null, true],
            ['isUserDefined', [], true, null, true],
            ['isInstantiable', [], true, null, true],
            ['isCloneable', [], true, null, true],
            ['getFileName', [], 'filename', null, 'filename'],
            ['getStartLine', [], 123, null, 123],
            ['getEndLine', [], 123, null, 123],
            ['getDocComment', [], null, null, false],
            ['getConstructor', [], null, null, null],
            ['hasMethod', ['foo'], true, null, true],
            ['getMethod', ['foo'], null, CoreReflectionException::class, null],
            ['getMethods', [], [], null, null],
            ['hasProperty', ['foo'], true, null, true],
            ['getProperty', ['foo'], null, CoreReflectionException::class, null],
            ['getProperties', [], [], null, null],
            ['hasConstant', ['foo'], true, null, true],
            ['getInterfaces', [], [], null, null],
            ['getInterfaceNames', [], ['a', 'b'], null, ['a', 'b']],
            ['isInterface', [], true, null, true],
            ['getTraits', [], [], null, null],
            ['getTraitNames', [], ['a', 'b'], null, ['a', 'b']],
            ['getTraitAliases', [], ['a', 'b'], null, ['a', 'b']],
            ['isTrait', [], true, null, true],
            ['isAbstract', [], true, null, true],
            ['isFinal', [], true, null, true],
            ['isReadOnly', [], true, null, true],
            ['getModifiers', [], 123, null, 123],
            ['isInstance', [new stdClass()], true, null, true],
            ['newInstance', [], null, NotImplemented::class, null],
            ['newInstanceWithoutConstructor', [], null, NotImplemented::class, null],
            ['newInstanceArgs', [], null, NotImplemented::class, null],
            ['getParentClass', [], null, null, null],
            ['isSubclassOf', ['\stdClass'], true, null, true],
            ['getStaticProperties', [], [], null, []],
            ['getDefaultProperties', [], ['foo' => 'bar'], null, null],
            ['isIterateable', [], true, null, true],
            ['implementsInterface', ['\Traversable'], true, null, true],
            ['getExtension', [], null, NotImplemented::class, null],
            ['getExtensionName', [], null, null, false],
            ['inNamespace', [], true, null, true],
            ['getNamespaceName', [], '', null, ''],
            ['getShortName', [], 'shortName', null, 'shortName'],
            ['getAttributes', [], [], null, null],
            ['isEnum', [], true, null, true],
        ];
    }

    /** @param list<mixed> $args */
    #[DataProvider('methodExpectationProvider')]
    public function testAdapterMethods(
        string $methodName,
        array $args,
        mixed $returnValue,
        string|null $expectedException,
        mixed $expectedReturnValue,
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

        if ($expectedReturnValue === null) {
            return;
        }

        self::assertSame($expectedReturnValue, $actualReturnValue);
    }

    public function testGetConstructor(): void
    {
        $betterReflectionObject = $this->createMock(BetterReflectionObject::class);
        $betterReflectionObject
            ->method('getConstructor')
            ->willReturn($this->createMock(BetterReflectionMethod::class));

        $reflectionObjectAdapter = new ReflectionObjectAdapter($betterReflectionObject);

        self::assertInstanceOf(ReflectionMethodAdapter::class, $reflectionObjectAdapter->getConstructor());
    }

    public function testGetInterfaces(): void
    {
        $betterReflectionObject = $this->createMock(BetterReflectionObject::class);
        $betterReflectionObject
            ->method('getInterfaces')
            ->willReturn([$this->createMock(BetterReflectionClass::class)]);

        $reflectionObjectAdapter = new ReflectionObjectAdapter($betterReflectionObject);

        self::assertCount(1, $reflectionObjectAdapter->getInterfaces());
        self::assertContainsOnlyInstancesOf(ReflectionClassAdapter::class, $reflectionObjectAdapter->getInterfaces());
    }

    public function testGetFileNameReturnsFalseWhenNoFileName(): void
    {
        $betterReflectionObject = $this->createMock(BetterReflectionObject::class);
        $betterReflectionObject
            ->method('getFileName')
            ->willReturn(null);

        $reflectionObjectAdapter = new ReflectionObjectAdapter($betterReflectionObject);

        self::assertFalse($reflectionObjectAdapter->getFileName());
    }

    public function testGetFileNameReturnsPathWithSystemDirectorySeparator(): void
    {
        $fileName = 'foo/bar\\foo/bar.php';

        $betterReflectionObject = $this->createMock(BetterReflectionObject::class);
        $betterReflectionObject
            ->method('getFileName')
            ->willReturn($fileName);

        $reflectionObjectAdapter = new ReflectionObjectAdapter($betterReflectionObject);

        self::assertSame(FileHelper::normalizeSystemPath($fileName), $reflectionObjectAdapter->getFileName());
    }

    public function testGetDocCommentReturnsFalseWhenNoDocComment(): void
    {
        $betterReflectionObject = $this->createMock(BetterReflectionObject::class);
        $betterReflectionObject
            ->method('getDocComment')
            ->willReturn(null);

        $reflectionObjectAdapter = new ReflectionObjectAdapter($betterReflectionObject);

        self::assertFalse($reflectionObjectAdapter->getDocComment());
    }

    public function testGetParentClass(): void
    {
        $betterReflectionObject = $this->createMock(BetterReflectionObject::class);
        $betterReflectionObject
            ->method('getParentClass')
            ->willReturn($this->createMock(BetterReflectionClass::class));

        $reflectionObjectAdapter = new ReflectionObjectAdapter($betterReflectionObject);

        self::assertInstanceOf(ReflectionClassAdapter::class, $reflectionObjectAdapter->getParentClass());
    }

    public function testGetParentClassReturnsFalseWhenNoParent(): void
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

    public function testGetMethod(): void
    {
        $betterReflectionObject = $this->createMock(BetterReflectionObject::class);
        $betterReflectionObject
            ->method('getMethod')
            ->with('doSomething')
            ->willReturn($this->createMock(BetterReflectionMethod::class));

        $reflectionObjectAdapter = new ReflectionObjectAdapter($betterReflectionObject);

        self::assertInstanceOf(ReflectionMethodAdapter::class, $reflectionObjectAdapter->getMethod('doSomething'));
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

    public function testGetMethods(): void
    {
        $betterReflectionObject = $this->createMock(BetterReflectionObject::class);
        $betterReflectionObject
            ->method('getMethods')
            ->willReturn([$this->createMock(BetterReflectionMethod::class)]);

        $reflectionObjectAdapter = new ReflectionObjectAdapter($betterReflectionObject);

        self::assertCount(1, $reflectionObjectAdapter->getMethods());
        self::assertContainsOnlyInstancesOf(ReflectionMethodAdapter::class, $reflectionObjectAdapter->getMethods());
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

    public function testHasPropertyReturnFalseWhenPropertyNameIsEmpty(): void
    {
        $betterReflectionObject  = $this->createMock(BetterReflectionObject::class);
        $reflectionObjectAdapter = new ReflectionObjectAdapter($betterReflectionObject);

        self::assertFalse($reflectionObjectAdapter->hasProperty(''));
    }

    public function testGetProperty(): void
    {
        $betterReflectionObject = $this->createMock(BetterReflectionObject::class);
        $betterReflectionObject
            ->method('getProperty')
            ->with('something')
            ->willReturn($this->createMock(BetterReflectionProperty::class));

        $reflectionObjectAdapter = new ReflectionObjectAdapter($betterReflectionObject);

        self::assertInstanceOf(ReflectionPropertyAdapter::class, $reflectionObjectAdapter->getProperty('something'));
    }

    public function testGetPropertyThrowsExceptionWhenPropertyNameIsEmpty(): void
    {
        $betterReflectionObject = $this->createMock(BetterReflectionObject::class);
        $betterReflectionObject
            ->method('getName')
            ->willReturn('Boo');

        $reflectionObjectAdapter = new ReflectionObjectAdapter($betterReflectionObject);

        $this->expectException(CoreReflectionException::class);
        $this->expectExceptionMessage('Property Boo::$ does not exist');
        $reflectionObjectAdapter->getProperty('');
    }

    public function testGetPropertyThrowsExceptionWhenPropertyDoesNotExist(): void
    {
        $betterReflectionObject = $this->createMock(BetterReflectionObject::class);
        $betterReflectionObject
            ->method('getName')
            ->willReturn('Boo');
        $betterReflectionObject
            ->method('getProperty')
            ->with('foo')
            ->willReturn(null);

        $reflectionObjectAdapter = new ReflectionObjectAdapter($betterReflectionObject);

        $this->expectException(CoreReflectionException::class);
        $this->expectExceptionMessage('Property Boo::$foo does not exist');
        $reflectionObjectAdapter->getProperty('foo');
    }

    public function testGetProperties(): void
    {
        $betterReflectionObject = $this->createMock(BetterReflectionObject::class);
        $betterReflectionObject
            ->method('getProperties')
            ->willReturn([$this->createMock(BetterReflectionProperty::class)]);

        $reflectionObjectAdapter = new ReflectionObjectAdapter($betterReflectionObject);

        self::assertCount(1, $reflectionObjectAdapter->getProperties());
        self::assertContainsOnlyInstancesOf(ReflectionPropertyAdapter::class, $reflectionObjectAdapter->getProperties());
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

    public function testGetStaticPropertyValueThrowsExceptionWhenPropertyNameIsEmpty(): void
    {
        $betterReflectionObject = $this->createMock(BetterReflectionObject::class);
        $betterReflectionObject
            ->method('getName')
            ->willReturn('SomeClass');

        $reflectionObjectAdapter = new ReflectionObjectAdapter($betterReflectionObject);

        $this->expectException(CoreReflectionException::class);
        $this->expectExceptionMessage('Property SomeClass::$ does not exist');
        $reflectionObjectAdapter->getStaticPropertyValue('');
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

    public function testSetStaticPropertyValueThrowsExceptionWhenPropertyNameIsEmpty(): void
    {
        $betterReflectionObject = $this->createMock(BetterReflectionObject::class);
        $betterReflectionObject
            ->method('getName')
            ->willReturn('SomeClass');

        $reflectionObjectAdapter = new ReflectionObjectAdapter($betterReflectionObject);

        $this->expectException(CoreReflectionException::class);
        $this->expectExceptionMessage('Class SomeClass does not have a property named ');
        $reflectionObjectAdapter->setStaticPropertyValue('', '');
    }

    public function testGetStaticPropertyValueThrowsExceptionWhenPropertyDoesNotExist(): void
    {
        $betterReflectionObject = $this->createMock(BetterReflectionObject::class);
        $betterReflectionObject
            ->method('getName')
            ->willReturn('Boo');
        $betterReflectionObject
            ->method('getProperty')
            ->willReturn(null);

        $reflectionObjectAdapter = new ReflectionObjectAdapter($betterReflectionObject);

        $this->expectException(CoreReflectionException::class);
        $this->expectExceptionMessage('Property Boo::$foo does not exist');
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
            ->method('getName')
            ->willReturn('Boo');
        $betterReflectionObject
            ->method('getProperty')
            ->willReturn(null);

        $reflectionObjectAdapter = new ReflectionObjectAdapter($betterReflectionObject);

        $this->expectException(CoreReflectionException::class);
        $this->expectExceptionMessage('Class Boo does not have a property named foo');
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
            ->method('getName')
            ->willReturn('Boo');
        $betterReflectionObject
            ->method('getProperty')
            ->with('foo')
            ->willReturn($betterReflectionProperty);

        $reflectionObjectAdapter = new ReflectionObjectAdapter($betterReflectionObject);

        $this->expectException(CoreReflectionException::class);
        $this->expectExceptionMessage('Property Boo::$foo does not exist');
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
            ->method('getName')
            ->willReturn('Boo');
        $betterReflectionObject
            ->method('getProperty')
            ->with('foo')
            ->willReturn($betterReflectionProperty);

        $reflectionObjectAdapter = new ReflectionObjectAdapter($betterReflectionObject);

        $this->expectException(CoreReflectionException::class);
        $this->expectExceptionMessage('Class Boo does not have a property named foo');
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

    public function testGetConstructorReturnsNullWhenNoConstructorExists(): void
    {
        $betterReflectionObject = $this->createMock(BetterReflectionObject::class);
        $betterReflectionObject
            ->method('getConstructor')
            ->willReturn(null);

        $reflectionObjectAdapter = new ReflectionObjectAdapter($betterReflectionObject);

        self::assertNull($reflectionObjectAdapter->getConstructor());
    }

    public function testGetExtensionNameReturnsFalseWhenNoExtensionName(): void
    {
        $betterReflectionObject = $this->createMock(BetterReflectionObject::class);
        $betterReflectionObject
            ->method('getExtensionName')
            ->willReturn(null);

        $reflectionObjectAdapter = new ReflectionObjectAdapter($betterReflectionObject);

        self::assertFalse($reflectionObjectAdapter->getExtensionName());
    }

    public function testGetConstantsWithFilter(): void
    {
        $betterReflectionObject                 = $this->createMock(BetterReflectionObject::class);
        $publicBetterReflectionClassConstant    = $this->createMock(BetterReflectionClassConstant::class);
        $privateBetterReflectionClassConstant   = $this->createMock(BetterReflectionClassConstant::class);
        $protectedBetterReflectionClassConstant = $this->createMock(BetterReflectionClassConstant::class);

        $publicBetterReflectionClassConstant
            ->method('getName')
            ->willReturn('PUBLIC_CONSTANT');

        $publicBetterReflectionClassConstant
            ->method('getValue')
            ->willReturn('public constant');

        $privateBetterReflectionClassConstant
            ->method('getName')
            ->willReturn('PRIVATE_CONSTANT');

        $privateBetterReflectionClassConstant
            ->method('getValue')
            ->willReturn('private constant');

        $protectedBetterReflectionClassConstant
            ->method('getName')
            ->willReturn('PROTECTED_CONSTANT');

        $protectedBetterReflectionClassConstant
            ->method('getValue')
            ->willReturn('protected constant');

        $betterReflectionObject
            ->method('getConstants')
            ->willReturnMap([
                [
                    0,
                    [
                        $publicBetterReflectionClassConstant->getName() => $publicBetterReflectionClassConstant,
                        $privateBetterReflectionClassConstant->getName() => $privateBetterReflectionClassConstant,
                        $protectedBetterReflectionClassConstant->getName() => $protectedBetterReflectionClassConstant,
                    ],
                ],
                [CoreReflectionClassConstant::IS_PUBLIC, [$publicBetterReflectionClassConstant->getName() => $publicBetterReflectionClassConstant]],
                [CoreReflectionClassConstant::IS_PRIVATE, [$privateBetterReflectionClassConstant->getName() => $privateBetterReflectionClassConstant]],
                [CoreReflectionClassConstant::IS_PROTECTED, [$protectedBetterReflectionClassConstant->getName() => $protectedBetterReflectionClassConstant]],
            ]);

        $reflectionObject = new ReflectionObjectAdapter($betterReflectionObject);

        $allConstants       = $reflectionObject->getConstants();
        $publicConstants    = $reflectionObject->getConstants(CoreReflectionClassConstant::IS_PUBLIC);
        $privateConstants   = $reflectionObject->getConstants(CoreReflectionClassConstant::IS_PRIVATE);
        $protectedConstants = $reflectionObject->getConstants(CoreReflectionClassConstant::IS_PROTECTED);

        self::assertCount(3, $allConstants);

        self::assertCount(1, $publicConstants);
        self::assertEquals([$publicBetterReflectionClassConstant->getName() => $publicBetterReflectionClassConstant->getValue()], $publicConstants);

        self::assertCount(1, $privateConstants);
        self::assertEquals([$privateBetterReflectionClassConstant->getName() => $privateBetterReflectionClassConstant->getValue()], $privateConstants);

        self::assertCount(1, $protectedConstants);
        self::assertEquals([$protectedBetterReflectionClassConstant->getName() => $protectedBetterReflectionClassConstant->getValue()], $protectedConstants);
    }

    public function testHasConstantReturnsFalseWhenConstantNameIsEmpty(): void
    {
        $betterReflectionObject  = $this->createMock(BetterReflectionObject::class);
        $reflectionObjectAdapter = new ReflectionObjectAdapter($betterReflectionObject);

        self::assertFalse($reflectionObjectAdapter->hasConstant(''));
    }

    public function testGetConstant(): void
    {
        $betterReflectionClassConstant = $this->createMock(BetterReflectionClassConstant::class);
        $betterReflectionClassConstant
            ->method('getValue')
            ->willReturn(123);

        $betterReflectionObject = $this->createMock(BetterReflectionObject::class);
        $betterReflectionObject
            ->method('getConstant')
            ->with('FOO')
            ->willReturn($betterReflectionClassConstant);

        $reflectionObjectAdapter = new ReflectionObjectAdapter($betterReflectionObject);

        self::assertSame(123, $reflectionObjectAdapter->getConstant('FOO'));
    }

    public function testGetConstantReturnsFalseWhenConstantNameIsEmpty(): void
    {
        $betterReflectionObject  = $this->createMock(BetterReflectionObject::class);
        $reflectionObjectAdapter = new ReflectionObjectAdapter($betterReflectionObject);

        self::assertFalse($reflectionObjectAdapter->getConstant(''));
    }

    public function testGetConstantReturnsFalseWhenConstantDoesNotExist(): void
    {
        $betterReflectionObject = $this->createMock(BetterReflectionObject::class);
        $betterReflectionObject
            ->method('getConstant')
            ->with('FOO')
            ->willReturn(null);

        $reflectionObjectAdapter = new ReflectionObjectAdapter($betterReflectionObject);

        self::assertFalse($reflectionObjectAdapter->getConstant('FOO'));
    }

    public function testGetReflectionConstant(): void
    {
        $betterReflectionObject = $this->createMock(BetterReflectionObject::class);

        $betterReflectionObject
            ->expects($this->once())
            ->method('getConstant')
            ->with('FOO')
            ->willReturn($this->createMock(BetterReflectionClassConstant::class));

        $reflectionObjectAdapter = new ReflectionObjectAdapter($betterReflectionObject);

        self::assertInstanceOf(ReflectionClassConstantAdapter::class, $reflectionObjectAdapter->getReflectionConstant('FOO'));
    }

    public function testGetReflectionConstantReturnsFalseWhenConstantNameIsEmpty(): void
    {
        $betterReflectionObject  = $this->createMock(BetterReflectionObject::class);
        $reflectionObjectAdapter = new ReflectionObjectAdapter($betterReflectionObject);

        self::assertFalse($reflectionObjectAdapter->getReflectionConstant(''));
    }

    public function testGetReflectionConstantReturnsFalseWhenConstantDoesNotExist(): void
    {
        $betterReflectionObject = $this->createMock(BetterReflectionObject::class);

        $betterReflectionObject
            ->expects($this->once())
            ->method('getConstant')
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
            ->method('getConstant')
            ->with('SOME_CONSTANT')
            ->willReturn($betterReflectionClassConstant);

        $reflectionObjectAdapter = new ReflectionObjectAdapter($betterReflectionObject);

        self::assertInstanceOf(ReflectionClassConstantAdapter::class, $reflectionObjectAdapter->getReflectionConstant('SOME_CONSTANT'));
    }

    public function testGetReflectionConstantsWithFilter(): void
    {
        $betterReflectionObject                 = $this->createMock(BetterReflectionObject::class);
        $publicBetterReflectionClassConstant    = $this->createMock(BetterReflectionClassConstant::class);
        $privateBetterReflectionClassConstant   = $this->createMock(BetterReflectionClassConstant::class);
        $protectedBetterReflectionClassConstant = $this->createMock(BetterReflectionClassConstant::class);

        $publicBetterReflectionClassConstant
            ->method('getName')
            ->willReturn('PUBLIC_CONSTANT');

        $privateBetterReflectionClassConstant
            ->method('getName')
            ->willReturn('PRIVATE_CONSTANT');

        $protectedBetterReflectionClassConstant
            ->method('getName')
            ->willReturn('PROTECTED_CONSTANT');

        $betterReflectionObject
            ->method('getConstants')
            ->willReturnMap([
                [
                    0,
                    [
                        $publicBetterReflectionClassConstant->getName() => $publicBetterReflectionClassConstant,
                        $privateBetterReflectionClassConstant->getName() => $privateBetterReflectionClassConstant,
                        $protectedBetterReflectionClassConstant->getName() => $protectedBetterReflectionClassConstant,
                    ],
                ],
                [CoreReflectionClassConstant::IS_PUBLIC, [$publicBetterReflectionClassConstant->getName() => $publicBetterReflectionClassConstant]],
                [CoreReflectionClassConstant::IS_PRIVATE, [$privateBetterReflectionClassConstant->getName() => $privateBetterReflectionClassConstant]],
                [CoreReflectionClassConstant::IS_PROTECTED, [$protectedBetterReflectionClassConstant->getName() => $protectedBetterReflectionClassConstant]],
            ]);

        $reflectionObject = new ReflectionObjectAdapter($betterReflectionObject);

        self::assertCount(3, $reflectionObject->getReflectionConstants());
        self::assertCount(1, $reflectionObject->getReflectionConstants(CoreReflectionClassConstant::IS_PUBLIC));
        self::assertCount(1, $reflectionObject->getReflectionConstants(CoreReflectionClassConstant::IS_PRIVATE));
        self::assertCount(1, $reflectionObject->getReflectionConstants(CoreReflectionClassConstant::IS_PROTECTED));
    }

    public function testGetConstantsReturnsClassConstantAdapter(): void
    {
        $betterReflectionObject        = $this->createMock(BetterReflectionObject::class);
        $betterReflectionClassConstant = $this->createMock(BetterReflectionClassConstant::class);

        $betterReflectionObject
            ->expects($this->once())
            ->method('getConstants')
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

        $betterReflectionClass = $this->getMockBuilder(BetterReflectionClass::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAttributes'])
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

        $attributes = $reflectionObjectAdapter->getAttributes($someAttributeClassName);

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

        $betterReflectionClass = $this->getMockBuilder(BetterReflectionClass::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAttributes'])
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

        self::assertCount(1, $reflectionObjectAdapter->getAttributes($className, ReflectionAttributeAdapter::IS_INSTANCEOF));
        self::assertCount(2, $reflectionObjectAdapter->getAttributes($parentClassName, ReflectionAttributeAdapter::IS_INSTANCEOF));
        self::assertCount(2, $reflectionObjectAdapter->getAttributes($interfaceName, ReflectionAttributeAdapter::IS_INSTANCEOF));
    }

    public function testGetAttributesThrowsExceptionForInvalidFlags(): void
    {
        $betterReflectionObject  = $this->createMock(BetterReflectionObject::class);
        $reflectionObjectAdapter = new ReflectionObjectAdapter($betterReflectionObject);

        $this->expectException(ValueError::class);
        $reflectionObjectAdapter->getAttributes(null, 123);
    }

    public function testGetTraits(): void
    {
        /** @phpstan-var class-string $traitOneClassName */
        $traitOneClassName = 'Trait1';
        /** @phpstan-var class-string $traitTwoClassName */
        $traitTwoClassName = 'Trait2';

        $betterReflectionTrait1 = $this->createMock(BetterReflectionClass::class);
        $betterReflectionTrait1
            ->method('getName')
            ->willReturn($traitOneClassName);
        $betterReflectionTrait2 = $this->createMock(BetterReflectionClass::class);
        $betterReflectionTrait2
            ->method('getName')
            ->willReturn($traitTwoClassName);

        $betterReflectionObject = $this->createMock(BetterReflectionObject::class);
        $betterReflectionObject
            ->method('getTraits')
            ->willReturn([$betterReflectionTrait1, $betterReflectionTrait2]);

        $reflectionObjectAdapter = new ReflectionObjectAdapter($betterReflectionObject);

        $traits = $reflectionObjectAdapter->getTraits();

        self::assertContainsOnlyInstancesOf(ReflectionClassAdapter::class, $traits);
        self::assertCount(2, $traits);
        self::assertArrayHasKey($traitOneClassName, $traits);
        self::assertArrayHasKey($traitTwoClassName, $traits);
    }

    public function testPropertyName(): void
    {
        $betterReflectionObject = $this->createMock(BetterReflectionObject::class);
        $betterReflectionObject
            ->method('getName')
            ->willReturn('foo');

        $reflectionObjectAdapter = new ReflectionObjectAdapter($betterReflectionObject);
        self::assertSame('foo', $reflectionObjectAdapter->name);
    }

    public function testUnknownProperty(): void
    {
        $betterReflectionObject  = $this->createMock(BetterReflectionObject::class);
        $reflectionObjectAdapter = new ReflectionObjectAdapter($betterReflectionObject);
        $this->expectException(OutOfBoundsException::class);
        $this->expectExceptionMessage('Property Roave\BetterReflection\Reflection\Adapter\ReflectionObject::$foo does not exist.');
        /** @phpstan-ignore-next-line */
        $reflectionObjectAdapter->foo;
    }

    public function testHasMethodReturnsFalseWhenMethodNameIsEmpty(): void
    {
        $betterReflectionObject  = $this->createMock(BetterReflectionObject::class);
        $reflectionObjectAdapter = new ReflectionObjectAdapter($betterReflectionObject);

        self::assertFalse($reflectionObjectAdapter->hasMethod(''));
    }

    public function testGetMethodThrowsExceptionWhenMethodNameIsEmpty(): void
    {
        $betterReflectionObject = $this->createMock(BetterReflectionObject::class);
        $betterReflectionObject
            ->method('getName')
            ->willReturn('SomeClass');

        $reflectionObjectAdapter = new ReflectionObjectAdapter($betterReflectionObject);

        $this->expectException(CoreReflectionException::class);
        $this->expectExceptionMessage('Method SomeClass::() does not exist');
        $reflectionObjectAdapter->getMethod('');
    }

    public function testGetMethodThrowsExceptionWhenMethodDoesNotExist(): void
    {
        $betterReflectionObject = $this->createMock(BetterReflectionObject::class);
        $betterReflectionObject
            ->method('getName')
            ->willReturn('SomeClass');

        $reflectionObjectAdapter = new ReflectionObjectAdapter($betterReflectionObject);

        $this->expectException(CoreReflectionException::class);
        $this->expectExceptionMessage('Method SomeClass::doesNotExist() does not exist');
        $reflectionObjectAdapter->getMethod('doesNotExist');
    }

    public function testGetMethodsWithFilter(): void
    {
        $betterReflectionObject          = $this->createMock(BetterReflectionObject::class);
        $publicBetterReflectionMethod    = $this->createMock(BetterReflectionMethod::class);
        $privateBetterReflectionMethod   = $this->createMock(BetterReflectionMethod::class);
        $protectedBetterReflectionMethod = $this->createMock(BetterReflectionMethod::class);

        $publicBetterReflectionMethod
            ->method('getName')
            ->willReturn('public');

        $privateBetterReflectionMethod
            ->method('getName')
            ->willReturn('private');

        $protectedBetterReflectionMethod
            ->method('getName')
            ->willReturn('protected');

        $betterReflectionObject
            ->method('getMethods')
            ->willReturnMap([
                [
                    0,
                    [
                        $publicBetterReflectionMethod->getName() => $publicBetterReflectionMethod,
                        $privateBetterReflectionMethod->getName() => $privateBetterReflectionMethod,
                        $protectedBetterReflectionMethod->getName() => $protectedBetterReflectionMethod,
                    ],
                ],
                [CoreReflectionMethod::IS_PUBLIC, [$publicBetterReflectionMethod->getName() => $publicBetterReflectionMethod]],
                [CoreReflectionMethod::IS_PRIVATE, [$privateBetterReflectionMethod->getName() => $privateBetterReflectionMethod]],
                [CoreReflectionMethod::IS_PROTECTED, [$protectedBetterReflectionMethod->getName() => $protectedBetterReflectionMethod]],
            ]);

        $reflectionObjectAdapter = new ReflectionObjectAdapter($betterReflectionObject);

        self::assertCount(3, $reflectionObjectAdapter->getMethods());
        self::assertCount(1, $reflectionObjectAdapter->getMethods(CoreReflectionMethod::IS_PUBLIC));
        self::assertCount(1, $reflectionObjectAdapter->getMethods(CoreReflectionMethod::IS_PRIVATE));
        self::assertCount(1, $reflectionObjectAdapter->getMethods(CoreReflectionMethod::IS_PROTECTED));
    }

    public function testGetPropertiesWithFilter(): void
    {
        $betterReflectionObject            = $this->createMock(BetterReflectionObject::class);
        $publicBetterReflectionProperty    = $this->createMock(BetterReflectionProperty::class);
        $privateBetterReflectionProperty   = $this->createMock(BetterReflectionProperty::class);
        $protectedBetterReflectionProperty = $this->createMock(BetterReflectionProperty::class);

        $publicBetterReflectionProperty
            ->method('getName')
            ->willReturn('public');

        $privateBetterReflectionProperty
            ->method('getName')
            ->willReturn('private');

        $protectedBetterReflectionProperty
            ->method('getName')
            ->willReturn('protected');

        $betterReflectionObject
            ->method('getProperties')
            ->willReturnMap([
                [
                    0,
                    [
                        $publicBetterReflectionProperty->getName() => $publicBetterReflectionProperty,
                        $privateBetterReflectionProperty->getName() => $privateBetterReflectionProperty,
                        $protectedBetterReflectionProperty->getName() => $protectedBetterReflectionProperty,
                    ],
                ],
                [CoreReflectionProperty::IS_PUBLIC, [$publicBetterReflectionProperty->getName() => $publicBetterReflectionProperty]],
                [CoreReflectionProperty::IS_PRIVATE, [$privateBetterReflectionProperty->getName() => $privateBetterReflectionProperty]],
                [CoreReflectionProperty::IS_PROTECTED, [$protectedBetterReflectionProperty->getName() => $protectedBetterReflectionProperty]],
            ]);

        $reflectionObjectAdapter = new ReflectionObjectAdapter($betterReflectionObject);

        self::assertCount(3, $reflectionObjectAdapter->getProperties());
        self::assertCount(1, $reflectionObjectAdapter->getProperties(CoreReflectionProperty::IS_PUBLIC));
        self::assertCount(1, $reflectionObjectAdapter->getProperties(CoreReflectionProperty::IS_PRIVATE));
        self::assertCount(1, $reflectionObjectAdapter->getProperties(CoreReflectionProperty::IS_PROTECTED));
    }
}
