<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection\Adapter;

use PHPUnit\Framework\TestCase;
use ReflectionClass as CoreReflectionClass;
use ReflectionException as CoreReflectionException;
use ReflectionObject as CoreReflectionObject;
use ReflectionProperty as CoreReflectionProperty;
use Roave\BetterReflection\Reflection\Adapter\Exception\NotImplemented;
use Roave\BetterReflection\Reflection\Adapter\ReflectionClassConstant as ReflectionClassConstantAdapter;
use Roave\BetterReflection\Reflection\Adapter\ReflectionObject as ReflectionObjectAdapter;
use Roave\BetterReflection\Reflection\ReflectionClass as BetterReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionClassConstant as BetterReflectionClassConstant;
use Roave\BetterReflection\Reflection\ReflectionMethod as BetterReflectionMethod;
use Roave\BetterReflection\Reflection\ReflectionObject as BetterReflectionObject;
use Roave\BetterReflection\Reflection\ReflectionProperty as BetterReflectionProperty;
use Roave\BetterReflection\Util\FileHelper;
use stdClass;
use TypeError;

use function array_combine;
use function array_map;
use function get_class_methods;

/**
 * @covers \Roave\BetterReflection\Reflection\Adapter\ReflectionObject
 */
class ReflectionObjectTest extends TestCase
{
    public function coreReflectionMethodNamesProvider(): array
    {
        $methods = get_class_methods(CoreReflectionObject::class);

        return array_combine($methods, array_map(static function (string $i): array {
            return [$i];
        }, $methods));
    }

    /**
     * @dataProvider coreReflectionMethodNamesProvider
     */
    public function testCoreReflectionMethods(string $methodName): void
    {
        $reflectionObjectAdapterReflection = new CoreReflectionClass(ReflectionObjectAdapter::class);
        self::assertTrue($reflectionObjectAdapterReflection->hasMethod($methodName));
    }

    public function methodExpectationProvider(): array
    {
        $mockMethod = $this->createMock(BetterReflectionMethod::class);

        $mockProperty = $this->createMock(BetterReflectionProperty::class);

        $mockClassLike = $this->createMock(BetterReflectionClass::class);

        $mockConstant = $this->createMock(BetterReflectionClassConstant::class);

        return [
            ['__toString', null, '', []],
            ['getName', null, '', []],
            ['isInternal', null, true, []],
            ['isUserDefined', null, true, []],
            ['isInstantiable', null, true, []],
            ['isCloneable', null, true, []],
            ['getFileName', null, '', []],
            ['getStartLine', null, 123, []],
            ['getEndLine', null, 123, []],
            ['getDocComment', null, '', []],
            ['getConstructor', null, $mockMethod, []],
            ['hasMethod', null, true, ['foo']],
            ['getMethod', null, $mockMethod, ['foo']],
            ['getMethods', null, [$mockMethod], []],
            ['hasProperty', null, true, ['foo']],
            ['getProperty', null, $mockProperty, ['foo']],
            ['getProperties', null, [$mockProperty], []],
            ['hasConstant', null, true, ['foo']],
            ['getConstant', null, 'a', ['foo']],
            ['getReflectionConstant', null, $mockConstant, ['foo']],
            ['getReflectionConstants', null, [$mockConstant], []],
            ['getInterfaces', null, [$mockClassLike], []],
            ['getInterfaceNames', null, ['a', 'b'], []],
            ['isInterface', null, true, []],
            ['getTraits', null, [$mockClassLike], []],
            ['getTraitNames', null, ['a', 'b'], []],
            ['getTraitAliases', null, ['a', 'b'], []],
            ['isTrait', null, true, []],
            ['isAbstract', null, true, []],
            ['isFinal', null, true, []],
            ['getModifiers', null, 123, []],
            ['isInstance', null, true, [new stdClass()]],
            ['newInstance', NotImplemented::class, null, ['foo']],
            ['newInstanceWithoutConstructor', NotImplemented::class, null, []],
            ['newInstanceArgs', NotImplemented::class, null, []],
            ['getParentClass', null, $mockClassLike, []],
            ['isSubclassOf', null, true, ['\stdClass']],
            ['getStaticProperties', null, [], []],
            ['getDefaultProperties', null, ['foo' => 'bar'], []],
            ['isIterateable', null, true, []],
            ['implementsInterface', null, true, ['\Traversable']],
            ['getExtension', NotImplemented::class, null, []],
            ['getExtensionName', null, null, []],
            ['inNamespace', null, true, []],
            ['getNamespaceName', null, '', []],
            ['getShortName', null, '', []],
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
        $reflectionStub = $this->createMock(BetterReflectionObject::class);

        if ($expectedException === null) {
            $reflectionStub->expects($this->once())
                ->method($methodName)
                ->with(...$args)
                ->will($this->returnValue($returnValue));
        }

        if ($expectedException !== null) {
            $this->expectException($expectedException);
        }

        $adapter = new ReflectionObjectAdapter($reflectionStub);
        $adapter->{$methodName}(...$args);
    }

    public function testExport(): void
    {
        $exported = ReflectionObjectAdapter::export(new stdClass(), true);

        self::assertIsString($exported);
        self::assertStringContainsString('stdClass', $exported);
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
            ->willReturn('foo');

        $betterReflectionObject = $this->createMock(BetterReflectionObject::class);
        $betterReflectionObject
            ->method('getMethods')
            ->willReturn([$betterReflectionMethod]);
        $betterReflectionObject
            ->method('hasMethod')
            ->with('foo')
            ->willReturn(true);

        $reflectionObjectAdapter = new ReflectionObjectAdapter($betterReflectionObject);

        self::assertTrue($reflectionObjectAdapter->hasMethod('foo'));
        self::assertTrue($reflectionObjectAdapter->hasMethod('FOO'));
    }

    public function testGetMethodIsCaseInsensitive(): void
    {
        $betterReflectionMethod = $this->createMock(BetterReflectionMethod::class);
        $betterReflectionMethod
            ->method('getName')
            ->willReturn('foo');

        $betterReflectionObject = $this->createMock(BetterReflectionObject::class);
        $betterReflectionObject
            ->method('getMethods')
            ->willReturn([$betterReflectionMethod]);
        $betterReflectionObject
            ->method('getMethod')
            ->with('foo')
            ->willReturn($betterReflectionMethod);

        $reflectionObjectAdapter = new ReflectionObjectAdapter($betterReflectionObject);

        self::assertSame('foo', $reflectionObjectAdapter->getMethod('foo')->getName());
        self::assertSame('foo', $reflectionObjectAdapter->getMethod('FOO')->getName());
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
        $reflectionObjectAdapter->setStaticPropertyValue('foo', null);
    }

    public function testGetExtensionNameReturnsFalseWhenNoExtensionName(): void
    {
        $betterReflectionObject = $this->createMock(BetterReflectionObject::class);
        $betterReflectionObject
            ->method('getExtensionName')
            ->willReturn(null);

        $betterReflectionObject = new ReflectionObjectAdapter($betterReflectionObject);

        self::assertFalse($betterReflectionObject->getExtensionName());
    }

    public function testIsInstanceReturnsNullWithNonObjectParameter(): void
    {
        $betterReflectionObject = $this->createMock(BetterReflectionObject::class);
        $betterReflectionObject
            ->method('isInstance')
            ->with('string')
            ->willThrowException(new TypeError());

        $betterReflectionObject = new ReflectionObjectAdapter($betterReflectionObject);

        self::assertNull($betterReflectionObject->isInstance('string'));
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
}
