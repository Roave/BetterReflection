<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection\Adapter;

use OutOfBoundsException;
use PHPUnit\Framework\TestCase;
use ReflectionClass as CoreReflectionClass;
use ReflectionClassConstant as CoreReflectionClassConstant;
use ReflectionException as CoreReflectionException;
use ReflectionMethod as CoreReflectionMethod;
use ReflectionProperty as CoreReflectionProperty;
use Roave\BetterReflection\Reflection\Adapter\Exception\NotImplemented;
use Roave\BetterReflection\Reflection\Adapter\ReflectionAttribute as ReflectionAttributeAdapter;
use Roave\BetterReflection\Reflection\Adapter\ReflectionClass as ReflectionClassAdapter;
use Roave\BetterReflection\Reflection\Adapter\ReflectionClassConstant as ReflectionClassConstantAdapter;
use Roave\BetterReflection\Reflection\Adapter\ReflectionMethod as ReflectionMethodAdapter;
use Roave\BetterReflection\Reflection\Adapter\ReflectionProperty as ReflectionPropertyAdapter;
use Roave\BetterReflection\Reflection\ReflectionAttribute as BetterReflectionAttribute;
use Roave\BetterReflection\Reflection\ReflectionClass as BetterReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionClassConstant as BetterReflectionClassConstant;
use Roave\BetterReflection\Reflection\ReflectionEnum as BetterReflectionEnum;
use Roave\BetterReflection\Reflection\ReflectionEnumCase as BetterReflectionEnumCase;
use Roave\BetterReflection\Reflection\ReflectionMethod as BetterReflectionMethod;
use Roave\BetterReflection\Reflection\ReflectionProperty as BetterReflectionProperty;
use Roave\BetterReflection\Util\FileHelper;
use Roave\BetterReflectionTest\Fixture\AutoloadableEnum;
use stdClass;
use ValueError;

use function array_combine;
use function array_map;
use function get_class_methods;

/** @covers \Roave\BetterReflection\Reflection\Adapter\ReflectionClass */
class ReflectionClassTest extends TestCase
{
    /** @return array<string, array{0: string}> */
    public static function coreReflectionMethodNamesProvider(): array
    {
        $methods = get_class_methods(CoreReflectionClass::class);

        return array_combine($methods, array_map(static fn (string $i): array => [$i], $methods));
    }

    /** @dataProvider coreReflectionMethodNamesProvider */
    public function testCoreReflectionMethods(string $methodName): void
    {
        $reflectionClassAdapterReflection = new CoreReflectionClass(ReflectionClassAdapter::class);

        self::assertTrue($reflectionClassAdapterReflection->hasMethod($methodName));
        self::assertSame(ReflectionClassAdapter::class, $reflectionClassAdapterReflection->getMethod($methodName)->getDeclaringClass()->getName());
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
    ): void {
        $reflectionStub = $this->createMock(BetterReflectionClass::class);

        if ($expectedException === null) {
            $reflectionStub->expects($this->once())
                ->method($methodName)
                ->with(...$args)
                ->willReturn($returnValue);
        }

        $adapter = new ReflectionClassAdapter($reflectionStub);

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
        $betterReflectionClass = $this->createMock(BetterReflectionClass::class);
        $betterReflectionClass
            ->method('getConstructor')
            ->willReturn($this->createMock(BetterReflectionMethod::class));

        $reflectionClassAdapter = new ReflectionClassAdapter($betterReflectionClass);

        self::assertInstanceOf(ReflectionMethodAdapter::class, $reflectionClassAdapter->getConstructor());
    }

    public function testGetInterfaces(): void
    {
        $betterReflectionClass = $this->createMock(BetterReflectionClass::class);
        $betterReflectionClass
            ->method('getInterfaces')
            ->willReturn([$this->createMock(BetterReflectionClass::class)]);

        $reflectionClassAdapter = new ReflectionClassAdapter($betterReflectionClass);

        self::assertContainsOnlyInstancesOf(ReflectionClassAdapter::class, $reflectionClassAdapter->getInterfaces());
    }

    public function testGetFileNameReturnsFalseWhenNoFileName(): void
    {
        $betterReflectionClass = $this->createMock(BetterReflectionClass::class);
        $betterReflectionClass
            ->method('getFileName')
            ->willReturn(null);

        $reflectionClassAdapter = new ReflectionClassAdapter($betterReflectionClass);

        self::assertFalse($reflectionClassAdapter->getFileName());
    }

    public function testGetFileNameReturnsPathWithSystemDirectorySeparator(): void
    {
        $fileName = 'foo/bar\\foo/bar.php';

        $betterReflectionClass = $this->createMock(BetterReflectionClass::class);
        $betterReflectionClass
            ->method('getFileName')
            ->willReturn($fileName);

        $reflectionClassAdapter = new ReflectionClassAdapter($betterReflectionClass);

        self::assertSame(FileHelper::normalizeSystemPath($fileName), $reflectionClassAdapter->getFileName());
    }

    public function testGetDocCommentReturnsFalseWhenNoDocComment(): void
    {
        $betterReflectionClass = $this->createMock(BetterReflectionClass::class);
        $betterReflectionClass
            ->method('getDocComment')
            ->willReturn(null);

        $reflectionClassAdapter = new ReflectionClassAdapter($betterReflectionClass);

        self::assertFalse($reflectionClassAdapter->getDocComment());
    }

    public function testGetParentClass(): void
    {
        $betterReflectionClass = $this->createMock(BetterReflectionClass::class);
        $betterReflectionClass
            ->method('getParentClass')
            ->willReturn($this->createMock(BetterReflectionClass::class));

        $reflectionClassAdapter = new ReflectionClassAdapter($betterReflectionClass);

        self::assertInstanceOf(ReflectionClassAdapter::class, $reflectionClassAdapter->getParentClass());
    }

    public function testGetParentClassReturnsFalseWhenNoParent(): void
    {
        $betterReflectionClass = $this->createMock(BetterReflectionClass::class);
        $betterReflectionClass
            ->method('getParentClass')
            ->willReturn(null);

        $reflectionClassAdapter = new ReflectionClassAdapter($betterReflectionClass);

        self::assertFalse($reflectionClassAdapter->getParentClass());
    }

    public function testHasMethodReturnsFalseWhenMethodNameIsEmpty(): void
    {
        $betterReflectionClass  = $this->createMock(BetterReflectionClass::class);
        $reflectionClassAdapter = new ReflectionClassAdapter($betterReflectionClass);

        self::assertFalse($reflectionClassAdapter->hasMethod(''));
    }

    public function testGetMethod(): void
    {
        $betterReflectionClass = $this->createMock(BetterReflectionClass::class);
        $betterReflectionClass
            ->method('getMethod')
            ->with('doSomething')
            ->willReturn($this->createMock(BetterReflectionMethod::class));

        $reflectionClassAdapter = new ReflectionClassAdapter($betterReflectionClass);

        self::assertInstanceOf(ReflectionMethodAdapter::class, $reflectionClassAdapter->getMethod('doSomething'));
    }

    public function testGetMethodThrowsExceptionWhenMethodNameIsEmpty(): void
    {
        $betterReflectionClass = $this->createMock(BetterReflectionClass::class);
        $betterReflectionClass
            ->method('getName')
            ->willReturn('SomeClass');

        $reflectionClassAdapter = new ReflectionClassAdapter($betterReflectionClass);

        $this->expectException(CoreReflectionException::class);
        $this->expectExceptionMessage('Method SomeClass::() does not exist');
        $reflectionClassAdapter->getMethod('');
    }

    public function testGetMethodThrowsExceptionWhenMethodDoesNotExist(): void
    {
        $betterReflectionClass = $this->createMock(BetterReflectionClass::class);
        $betterReflectionClass
            ->method('getName')
            ->willReturn('SomeClass');

        $reflectionClassAdapter = new ReflectionClassAdapter($betterReflectionClass);

        $this->expectException(CoreReflectionException::class);
        $this->expectExceptionMessage('Method SomeClass::doesNotExist() does not exist');
        $reflectionClassAdapter->getMethod('doesNotExist');
    }

    public function testGetMethods(): void
    {
        $betterReflectionClass = $this->createMock(BetterReflectionClass::class);
        $betterReflectionClass
            ->method('getMethods')
            ->willReturn([$this->createMock(BetterReflectionMethod::class)]);

        $reflectionClassAdapter = new ReflectionClassAdapter($betterReflectionClass);

        self::assertCount(1, $reflectionClassAdapter->getMethods());
        self::assertContainsOnlyInstancesOf(ReflectionMethodAdapter::class, $reflectionClassAdapter->getMethods());
    }

    public function testGetMethodsFilter(): void
    {
        $publicBetterReflectionMethod = $this->createMock(BetterReflectionMethod::class);
        $publicBetterReflectionMethod
            ->method('getName')
            ->willReturn('publicMethod');

        $privateBetterReflectionMethod = $this->createMock(BetterReflectionMethod::class);
        $privateBetterReflectionMethod
            ->method('getName')
            ->willReturn('privateMethod');

        $betterReflectionClass = $this->createMock(BetterReflectionClass::class);
        $betterReflectionClass
            ->method('getMethods')
            ->willReturnMap([
                [0, [$publicBetterReflectionMethod, $privateBetterReflectionMethod]],
                [CoreReflectionMethod::IS_PUBLIC, [$publicBetterReflectionMethod]],
                [CoreReflectionMethod::IS_PRIVATE, [$privateBetterReflectionMethod]],
            ]);

        $reflectionClassAdapter = new ReflectionClassAdapter($betterReflectionClass);

        self::assertCount(2, $reflectionClassAdapter->getMethods());

        $publicMethods = $reflectionClassAdapter->getMethods(CoreReflectionMethod::IS_PUBLIC);

        self::assertCount(1, $publicMethods);
        self::assertSame($publicBetterReflectionMethod->getName(), $publicMethods[0]->getName());

        $privateMethods = $reflectionClassAdapter->getMethods(CoreReflectionMethod::IS_PRIVATE);

        self::assertCount(1, $privateMethods);
        self::assertSame($privateBetterReflectionMethod->getName(), $privateMethods[0]->getName());
    }

    public function testGetReflectionConstantsWithFilter(): void
    {
        $betterReflectionClass                  = $this->createMock(BetterReflectionClass::class);
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

        $betterReflectionClass
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

        $reflectionClassAdapter = new ReflectionClassAdapter($betterReflectionClass);

        $allConstants       = $reflectionClassAdapter->getReflectionConstants();
        $publicConstants    = $reflectionClassAdapter->getReflectionConstants(CoreReflectionClassConstant::IS_PUBLIC);
        $privateConstants   = $reflectionClassAdapter->getReflectionConstants(CoreReflectionClassConstant::IS_PRIVATE);
        $protectedConstants = $reflectionClassAdapter->getReflectionConstants(CoreReflectionClassConstant::IS_PROTECTED);

        self::assertCount(3, $allConstants);
        self::assertContainsOnlyInstancesOf(ReflectionClassConstantAdapter::class, $allConstants);

        self::assertCount(1, $publicConstants);
        self::assertSame($publicBetterReflectionClassConstant->getName(), $publicConstants[0]->getName());

        self::assertCount(1, $privateConstants);
        self::assertSame($privateBetterReflectionClassConstant->getName(), $privateConstants[0]->getName());

        self::assertCount(1, $protectedConstants);
        self::assertSame($protectedBetterReflectionClassConstant->getName(), $protectedConstants[0]->getName());
    }

    public function testGetConstantsWithFilter(): void
    {
        $betterReflectionClass                  = $this->createMock(BetterReflectionClass::class);
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

        $betterReflectionClass
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

        $reflectionClassAdapter = new ReflectionClassAdapter($betterReflectionClass);

        $allConstants       = $reflectionClassAdapter->getConstants();
        $publicConstants    = $reflectionClassAdapter->getConstants(CoreReflectionClassConstant::IS_PUBLIC);
        $privateConstants   = $reflectionClassAdapter->getConstants(CoreReflectionClassConstant::IS_PRIVATE);
        $protectedConstants = $reflectionClassAdapter->getConstants(CoreReflectionClassConstant::IS_PROTECTED);

        self::assertCount(3, $allConstants);

        self::assertCount(1, $publicConstants);
        self::assertEquals([$publicBetterReflectionClassConstant->getName() => $publicBetterReflectionClassConstant->getValue()], $publicConstants);

        self::assertCount(1, $privateConstants);
        self::assertEquals([$privateBetterReflectionClassConstant->getName() => $privateBetterReflectionClassConstant->getValue()], $privateConstants);

        self::assertCount(1, $protectedConstants);
        self::assertEquals([$protectedBetterReflectionClassConstant->getName() => $protectedBetterReflectionClassConstant->getValue()], $protectedConstants);
    }

    public function testIsSubclassOfWithObject(): void
    {
        $betterReflectionClass = $this->createMock(BetterReflectionClass::class);
        $betterReflectionClass
            ->method('getParentClassNames')
            ->willReturn(['Foo']);
        $betterReflectionClass
            ->method('isSubclassOf')
            ->with('Foo')
            ->willReturn(true);

        $reflectionClassAdapter = new ReflectionClassAdapter($betterReflectionClass);

        $fooClassMock = $this->createMock(CoreReflectionClass::class);
        $fooClassMock
            ->method('getName')
            ->willReturn('Foo');

        self::assertTrue($reflectionClassAdapter->isSubclassOf($fooClassMock));
    }

    public function testIsSubclassOfIsCaseInsensitive(): void
    {
        $betterReflectionClass = $this->createMock(BetterReflectionClass::class);
        $betterReflectionClass
            ->method('getParentClassNames')
            ->willReturn(['Foo']);
        $betterReflectionClass
            ->method('isSubclassOf')
            ->with('Foo')
            ->willReturn(true);

        $reflectionClassAdapter = new ReflectionClassAdapter($betterReflectionClass);

        self::assertTrue($reflectionClassAdapter->isSubclassOf('Foo'));
        self::assertTrue($reflectionClassAdapter->isSubclassOf('foo'));
        self::assertTrue($reflectionClassAdapter->isSubclassOf('FoO'));
    }

    public function testIsSubclassOfChecksAlsoImplementedInterfaces(): void
    {
        $betterReflectionClass = $this->createMock(BetterReflectionClass::class);
        $betterReflectionClass
            ->method('getParentClassNames')
            ->willReturn([]);
        $betterReflectionClass
            ->method('isSubclassOf')
            ->with('Foo')
            ->willReturn(false);
        $betterReflectionClass
            ->method('getInterfaceNames')
            ->willReturn(['Foo']);
        $betterReflectionClass
            ->method('implementsInterface')
            ->with('Foo')
            ->willReturn(true);

        $reflectionClassAdapter = new ReflectionClassAdapter($betterReflectionClass);

        self::assertTrue($reflectionClassAdapter->isSubclassOf('Foo'));
    }

    public function testImplementsInterfaceWithObject(): void
    {
        $betterReflectionClass = $this->createMock(BetterReflectionClass::class);
        $betterReflectionClass
            ->method('getInterfaceNames')
            ->willReturn(['Foo']);
        $betterReflectionClass
            ->method('implementsInterface')
            ->with('Foo')
            ->willReturn(true);

        $reflectionClassAdapter = new ReflectionClassAdapter($betterReflectionClass);

        $fooClassMock = $this->createMock(CoreReflectionClass::class);
        $fooClassMock
            ->method('getName')
            ->willReturn('Foo');

        self::assertTrue($reflectionClassAdapter->implementsInterface($fooClassMock));
    }

    public function testImplementsInterfaceIsCaseInsensitive(): void
    {
        $betterReflectionClass = $this->createMock(BetterReflectionClass::class);
        $betterReflectionClass
            ->method('getInterfaceNames')
            ->willReturn(['Foo']);
        $betterReflectionClass
            ->method('implementsInterface')
            ->with('Foo')
            ->willReturn(true);

        $reflectionClassAdapter = new ReflectionClassAdapter($betterReflectionClass);

        self::assertTrue($reflectionClassAdapter->implementsInterface('Foo'));
        self::assertTrue($reflectionClassAdapter->implementsInterface('foo'));
        self::assertTrue($reflectionClassAdapter->implementsInterface('FoO'));
    }

    public function testHasPropertyReturnFalseWhenPropertyNameIsEmpty(): void
    {
        $betterReflectionClass  = $this->createMock(BetterReflectionClass::class);
        $reflectionClassAdapter = new ReflectionClassAdapter($betterReflectionClass);

        self::assertFalse($reflectionClassAdapter->hasProperty(''));
    }

    public function testGetProperty(): void
    {
        $betterReflectionClass = $this->createMock(BetterReflectionClass::class);
        $betterReflectionClass
            ->method('getProperty')
            ->with('something')
            ->willReturn($this->createMock(BetterReflectionProperty::class));

        $reflectionClassAdapter = new ReflectionClassAdapter($betterReflectionClass);

        self::assertInstanceOf(ReflectionPropertyAdapter::class, $reflectionClassAdapter->getProperty('something'));
    }

    public function testGetPropertyThrowsExceptionWhenPropertyNameIsEmpty(): void
    {
        $betterReflectionClass = $this->createMock(BetterReflectionClass::class);
        $betterReflectionClass
            ->method('getName')
            ->willReturn('Boo');

        $reflectionClassAdapter = new ReflectionClassAdapter($betterReflectionClass);

        $this->expectException(CoreReflectionException::class);
        $this->expectExceptionMessage('Property Boo::$ does not exist');
        $reflectionClassAdapter->getProperty('');
    }

    public function testGetPropertyThrowsExceptionWhenPropertyDoesNotExist(): void
    {
        $betterReflectionClass = $this->createMock(BetterReflectionClass::class);
        $betterReflectionClass
            ->method('getName')
            ->willReturn('Boo');
        $betterReflectionClass
            ->method('getProperty')
            ->with('foo')
            ->willReturn(null);

        $reflectionClassAdapter = new ReflectionClassAdapter($betterReflectionClass);

        $this->expectException(CoreReflectionException::class);
        $this->expectExceptionMessage('Property Boo::$foo does not exist');
        $reflectionClassAdapter->getProperty('foo');
    }

    public function testGetProperties(): void
    {
        $betterReflectionClass = $this->createMock(BetterReflectionClass::class);
        $betterReflectionClass
            ->method('getProperties')
            ->willReturn([$this->createMock(BetterReflectionProperty::class)]);

        $reflectionClassAdapter = new ReflectionClassAdapter($betterReflectionClass);

        self::assertCount(1, $reflectionClassAdapter->getProperties());
        self::assertContainsOnlyInstancesOf(ReflectionPropertyAdapter::class, $reflectionClassAdapter->getProperties());
    }

    public function testGetPropertiesFilter(): void
    {
        $publicBetterReflectionProperty = $this->createMock(BetterReflectionProperty::class);
        $publicBetterReflectionProperty
            ->method('getName')
            ->willReturn('publicProperty');

        $privateBetterReflectionProperty = $this->createMock(BetterReflectionProperty::class);
        $privateBetterReflectionProperty
            ->method('getName')
            ->willReturn('privateProperty');

        $betterReflectionClass = $this->createMock(BetterReflectionClass::class);
        $betterReflectionClass
            ->method('getProperties')
            ->willReturnMap([
                [0, [$publicBetterReflectionProperty, $privateBetterReflectionProperty]],
                [CoreReflectionProperty::IS_PUBLIC, [$publicBetterReflectionProperty]],
                [CoreReflectionProperty::IS_PRIVATE, [$privateBetterReflectionProperty]],
            ]);

        $reflectionClassAdapter = new ReflectionClassAdapter($betterReflectionClass);

        self::assertCount(2, $reflectionClassAdapter->getProperties());

        $publicProperties = $reflectionClassAdapter->getProperties(CoreReflectionProperty::IS_PUBLIC);

        self::assertCount(1, $publicProperties);
        self::assertSame($publicBetterReflectionProperty->getName(), $publicProperties[0]->getName());

        $privateProperties = $reflectionClassAdapter->getProperties(CoreReflectionProperty::IS_PRIVATE);

        self::assertCount(1, $privateProperties);
        self::assertSame($privateBetterReflectionProperty->getName(), $privateProperties[0]->getName());
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

        $betterReflectionClass = $this->createMock(BetterReflectionClass::class);
        $betterReflectionClass
            ->method('getProperty')
            ->with('foo')
            ->willReturn($betterReflectionProperty);

        $reflectionClassAdapter = new ReflectionClassAdapter($betterReflectionClass);

        self::assertSame(123, $reflectionClassAdapter->getStaticPropertyValue('foo'));
    }

    public function testGetStaticPropertyValueThrowsExceptionWhenPropertyNameIsEmpty(): void
    {
        $betterReflectionClass = $this->createMock(BetterReflectionClass::class);
        $betterReflectionClass
            ->method('getName')
            ->willReturn('SomeClass');

        $reflectionClassAdapter = new ReflectionClassAdapter($betterReflectionClass);

        $this->expectException(CoreReflectionException::class);
        $this->expectExceptionMessage('Property SomeClass::$ does not exist');
        $reflectionClassAdapter->getStaticPropertyValue('');
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

        $betterReflectionClass = $this->createMock(BetterReflectionClass::class);
        $betterReflectionClass
            ->method('getProperty')
            ->with('foo')
            ->willReturn($betterReflectionProperty);

        $reflectionClassAdapter = new ReflectionClassAdapter($betterReflectionClass);

        $reflectionClassAdapter->setStaticPropertyValue('foo', 123);
    }

    public function testSetStaticPropertyValueThrowsExceptionWhenPropertyNameIsEmpty(): void
    {
        $betterReflectionClass = $this->createMock(BetterReflectionClass::class);
        $betterReflectionClass
            ->method('getName')
            ->willReturn('SomeClass');

        $reflectionClassAdapter = new ReflectionClassAdapter($betterReflectionClass);

        $this->expectException(CoreReflectionException::class);
        $this->expectExceptionMessage('Class SomeClass does not have a property named ');
        $reflectionClassAdapter->setStaticPropertyValue('', '');
    }

    public function testGetStaticPropertyValueThrowsExceptionWhenPropertyDoesNotExist(): void
    {
        $betterReflectionClass = $this->createMock(BetterReflectionClass::class);
        $betterReflectionClass
            ->method('getName')
            ->willReturn('Boo');
        $betterReflectionClass
            ->method('getProperty')
            ->with('foo')
            ->willReturn(null);

        $reflectionClassAdapter = new ReflectionClassAdapter($betterReflectionClass);

        $this->expectException(CoreReflectionException::class);
        $this->expectExceptionMessage('Property Boo::$foo does not exist');
        $reflectionClassAdapter->getStaticPropertyValue('foo');
    }

    public function testGetStaticPropertyValueReturnsDefaultValueWhenPropertyDoesNotExist(): void
    {
        $betterReflectionClass = $this->createMock(BetterReflectionClass::class);
        $betterReflectionClass
            ->method('getProperty')
            ->with('foo')
            ->willReturn(null);

        $reflectionClassAdapter = new ReflectionClassAdapter($betterReflectionClass);

        self::assertSame('default', $reflectionClassAdapter->getStaticPropertyValue('foo', 'default'));
    }

    public function testSetStaticPropertyValueThrowsExceptionWhenPropertyDoesNotExist(): void
    {
        $betterReflectionClass = $this->createMock(BetterReflectionClass::class);
        $betterReflectionClass
            ->method('getName')
            ->willReturn('Boo');
        $betterReflectionClass
            ->method('getProperty')
            ->with('foo')
            ->willReturn(null);

        $reflectionClassAdapter = new ReflectionClassAdapter($betterReflectionClass);

        $this->expectException(CoreReflectionException::class);
        $this->expectExceptionMessage('Class Boo does not have a property named foo');
        $reflectionClassAdapter->setStaticPropertyValue('foo', null);
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

        $betterReflectionClass = $this->createMock(BetterReflectionClass::class);
        $betterReflectionClass
            ->method('getName')
            ->willReturn('Boo');
        $betterReflectionClass
            ->method('getProperty')
            ->with('foo')
            ->willReturn($betterReflectionProperty);

        $reflectionClassAdapter = new ReflectionClassAdapter($betterReflectionClass);

        $this->expectException(CoreReflectionException::class);
        $this->expectExceptionMessage('Property Boo::$foo does not exist');
        $reflectionClassAdapter->getStaticPropertyValue('foo');
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

        $betterReflectionClass = $this->createMock(BetterReflectionClass::class);
        $betterReflectionClass
            ->method('getName')
            ->willReturn('Boo');
        $betterReflectionClass
            ->method('getProperty')
            ->with('foo')
            ->willReturn($betterReflectionProperty);

        $reflectionClassAdapter = new ReflectionClassAdapter($betterReflectionClass);

        $this->expectException(CoreReflectionException::class);
        $this->expectExceptionMessage('Class Boo does not have a property named foo');
        $reflectionClassAdapter->setStaticPropertyValue('foo', null);
    }

    public function testIsIterable(): void
    {
        $betterReflectionClass = $this->createMock(BetterReflectionClass::class);
        $betterReflectionClass
            ->method('isIterateable')
            ->willReturn(true);

        $reflectionClassAdapter = new ReflectionClassAdapter($betterReflectionClass);

        self::assertTrue($reflectionClassAdapter->isIterable());
    }

    public function testGetExtensionNameReturnsFalseWhenNoExtensionName(): void
    {
        $betterReflectionClass = $this->createMock(BetterReflectionClass::class);
        $betterReflectionClass
            ->method('getExtensionName')
            ->willReturn(null);

        $reflectionClassAdapter = new ReflectionClassAdapter($betterReflectionClass);

        self::assertFalse($reflectionClassAdapter->getExtensionName());
    }

    public function testGetConstructorReturnsNullWhenNoConstructorExists(): void
    {
        $betterReflectionClass = $this->createMock(BetterReflectionClass::class);
        $betterReflectionClass
            ->method('getConstructor')
            ->willReturn(null);

        $reflectionClassAdapter = new ReflectionClassAdapter($betterReflectionClass);

        self::assertNull($reflectionClassAdapter->getConstructor());
    }

    public function testHasConstantReturnsFalseWhenConstantNameIsEmpty(): void
    {
        $betterReflectionClass  = $this->createMock(BetterReflectionClass::class);
        $reflectionClassAdapter = new ReflectionClassAdapter($betterReflectionClass);

        self::assertFalse($reflectionClassAdapter->hasConstant(''));
    }

    public function testGetConstant(): void
    {
        $betterReflectionClassConstant = $this->createMock(BetterReflectionClassConstant::class);
        $betterReflectionClassConstant
            ->method('getValue')
            ->willReturn(123);

        $betterReflectionClass = $this->createMock(BetterReflectionClass::class);
        $betterReflectionClass
            ->method('getConstant')
            ->with('FOO')
            ->willReturn($betterReflectionClassConstant);

        $reflectionClassAdapter = new ReflectionClassAdapter($betterReflectionClass);

        self::assertSame(123, $reflectionClassAdapter->getConstant('FOO'));
    }

    public function testGetConstantReturnsFalseWhenConstantNameIsEmpty(): void
    {
        $betterReflectionClass  = $this->createMock(BetterReflectionClass::class);
        $reflectionClassAdapter = new ReflectionClassAdapter($betterReflectionClass);

        self::assertFalse($reflectionClassAdapter->getConstant(''));
    }

    public function testGetConstantReturnsFalseWhenConstantDoesNotExist(): void
    {
        $betterReflectionClass = $this->createMock(BetterReflectionClass::class);
        $betterReflectionClass
            ->method('getConstant')
            ->with('FOO')
            ->willReturn(null);

        $reflectionClassAdapter = new ReflectionClassAdapter($betterReflectionClass);

        self::assertFalse($reflectionClassAdapter->getConstant('FOO'));
    }

    public function testGetReflectionConstant(): void
    {
        $betterReflectionClass = $this->createMock(BetterReflectionClass::class);
        $betterReflectionClass
            ->method('getConstant')
            ->with('FOO')
            ->willReturn($this->createMock(BetterReflectionClassConstant::class));

        $reflectionClassAdapter = new ReflectionClassAdapter($betterReflectionClass);

        self::assertInstanceOf(ReflectionClassConstantAdapter::class, $reflectionClassAdapter->getReflectionConstant('FOO'));
    }

    public function testGetReflectionConstantReturnsFalseWhenConstantDoesNotExist(): void
    {
        $betterReflectionClass = $this->createMock(BetterReflectionClass::class);
        $betterReflectionClass
            ->method('getConstant')
            ->with('FOO')
            ->willReturn(null);

        $reflectionClassAdapter = new ReflectionClassAdapter($betterReflectionClass);

        self::assertFalse($reflectionClassAdapter->getReflectionConstant('FOO'));
    }

    public function testGetReflectionConstantReturnsFalseWhenConstantNameIsEmpty(): void
    {
        $betterReflectionClass  = $this->createMock(BetterReflectionClass::class);
        $reflectionClassAdapter = new ReflectionClassAdapter($betterReflectionClass);

        self::assertFalse($reflectionClassAdapter->getReflectionConstant(''));
    }

    public function testPropertyName(): void
    {
        $betterReflectionClass = $this->createMock(BetterReflectionClass::class);
        $betterReflectionClass
            ->method('getName')
            ->willReturn('Foo');

        $reflectionClassAdapter = new ReflectionClassAdapter($betterReflectionClass);
        self::assertSame('Foo', $reflectionClassAdapter->name);
    }

    public function testUnknownProperty(): void
    {
        $betterReflectionClass  = $this->createMock(BetterReflectionClass::class);
        $reflectionClassAdapter = new ReflectionClassAdapter($betterReflectionClass);
        $this->expectException(OutOfBoundsException::class);
        $this->expectExceptionMessage('Property Roave\BetterReflection\Reflection\Adapter\ReflectionClass::$foo does not exist.');
        /** @phpstan-ignore-next-line */
        $reflectionClassAdapter->foo;
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

        $betterReflectionClass = $this->createMock(BetterReflectionClass::class);
        $betterReflectionClass
            ->method('getAttributes')
            ->willReturn($betterReflectionAttributes);

        $reflectionClassAdapter = new ReflectionClassAdapter($betterReflectionClass);
        $attributes             = $reflectionClassAdapter->getAttributes();

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

        $reflectionClassAdapter = new ReflectionClassAdapter($betterReflectionClass);
        $attributes             = $reflectionClassAdapter->getAttributes($someAttributeClassName);

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

        $reflectionClassAdapter = new ReflectionClassAdapter($betterReflectionClass);

        self::assertCount(1, $reflectionClassAdapter->getAttributes($className, ReflectionAttributeAdapter::IS_INSTANCEOF));
        self::assertCount(2, $reflectionClassAdapter->getAttributes($parentClassName, ReflectionAttributeAdapter::IS_INSTANCEOF));
        self::assertCount(2, $reflectionClassAdapter->getAttributes($interfaceName, ReflectionAttributeAdapter::IS_INSTANCEOF));
    }

    public function testGetAttributesThrowsExceptionForInvalidFlags(): void
    {
        $betterReflectionClass  = $this->createMock(BetterReflectionClass::class);
        $reflectionClassAdapter = new ReflectionClassAdapter($betterReflectionClass);

        $this->expectException(ValueError::class);
        $reflectionClassAdapter->getAttributes(null, 123);
    }

    public function testHasConstantWithEnumCase(): void
    {
        $betterReflectionEnum = $this->createMock(BetterReflectionEnum::class);
        $betterReflectionEnum
            ->method('hasCase')
            ->with('ENUM_CASE')
            ->willReturn(true);

        $reflectionClassAdapter = new ReflectionClassAdapter($betterReflectionEnum);

        self::assertTrue($reflectionClassAdapter->hasConstant('ENUM_CASE'));
    }

    /** @runInSeparateProcess */
    public function testGetConstantWithEnumCase(): void
    {
        $betterReflectionEnumCase = $this->createMock(BetterReflectionEnumCase::class);
        $betterReflectionEnumCase
            ->method('getName')
            ->willReturn('ENUM_CASE');

        $betterReflectionEnum = $this->createMock(BetterReflectionEnum::class);
        $betterReflectionEnum
            ->method('getName')
            ->willReturn(AutoloadableEnum::class);
        $betterReflectionEnum
            ->method('hasCase')
            ->with('ENUM_CASE')
            ->willReturn(true);
        $betterReflectionEnum
            ->method('getCase')
            ->with('ENUM_CASE')
            ->willReturn($betterReflectionEnumCase);

        $betterReflectionEnumCase
            ->method('getDeclaringClass')
            ->willReturn($betterReflectionEnum);

        $reflectionClassAdapter = new ReflectionClassAdapter($betterReflectionEnum);

        self::assertInstanceOf(AutoloadableEnum::class, $reflectionClassAdapter->getConstant('ENUM_CASE'));
    }

    public function testGetReflectionConstantWithEnumCase(): void
    {
        $betterReflectionEnumCase = $this->createMock(BetterReflectionEnumCase::class);

        $betterReflectionEnum = $this->createMock(BetterReflectionEnum::class);
        $betterReflectionEnum
            ->method('hasCase')
            ->with('ENUM_CASE')
            ->willReturn(true);
        $betterReflectionEnum
            ->method('getCase')
            ->with('ENUM_CASE')
            ->willReturn($betterReflectionEnumCase);

        $reflectionClassAdapter = new ReflectionClassAdapter($betterReflectionEnum);

        self::assertInstanceOf(ReflectionClassConstantAdapter::class, $reflectionClassAdapter->getReflectionConstant('ENUM_CASE'));
    }

    public function testGetReflectionConstantsWithFilterAndEnumCase(): void
    {
        $betterReflectionEnum                   = $this->createMock(BetterReflectionEnum::class);
        $betterReflectionEnumCase               = $this->createMock(BetterReflectionEnumCase::class);
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

        $betterReflectionEnum
            ->method('getCases')
            ->willReturn(['enum_case' => $betterReflectionEnumCase]);

        $betterReflectionEnum
            ->method('getConstants')
            ->willReturnOnConsecutiveCalls(
                [
                    $publicBetterReflectionClassConstant->getName() => $publicBetterReflectionClassConstant,
                    $privateBetterReflectionClassConstant->getName() => $privateBetterReflectionClassConstant,
                    $protectedBetterReflectionClassConstant->getName() => $protectedBetterReflectionClassConstant,
                ],
                [$publicBetterReflectionClassConstant->getName() => $publicBetterReflectionClassConstant],
                [$privateBetterReflectionClassConstant->getName() => $privateBetterReflectionClassConstant],
                [$protectedBetterReflectionClassConstant->getName() => $protectedBetterReflectionClassConstant],
            );

        $reflectionClassAdapter = new ReflectionClassAdapter($betterReflectionEnum);

        self::assertCount(4, $reflectionClassAdapter->getReflectionConstants());
        self::assertCount(2, $reflectionClassAdapter->getReflectionConstants(CoreReflectionProperty::IS_PUBLIC));
        self::assertCount(1, $reflectionClassAdapter->getReflectionConstants(CoreReflectionProperty::IS_PRIVATE));
        self::assertCount(1, $reflectionClassAdapter->getReflectionConstants(CoreReflectionProperty::IS_PROTECTED));
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

        $betterReflectionClass = $this->createMock(BetterReflectionClass::class);
        $betterReflectionClass
            ->method('getTraits')
            ->willReturn([$betterReflectionTrait1, $betterReflectionTrait2]);

        $reflectionClassAdapter = new ReflectionClassAdapter($betterReflectionClass);

        $traits = $reflectionClassAdapter->getTraits();

        self::assertContainsOnlyInstancesOf(ReflectionClassAdapter::class, $traits);
        self::assertCount(2, $traits);
        self::assertArrayHasKey($traitOneClassName, $traits);
        self::assertArrayHasKey($traitTwoClassName, $traits);
    }
}
