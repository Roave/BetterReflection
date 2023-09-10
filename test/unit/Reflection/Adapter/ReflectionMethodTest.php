<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection\Adapter;

use Closure;
use OutOfBoundsException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass as CoreReflectionClass;
use ReflectionException as CoreReflectionException;
use ReflectionMethod as CoreReflectionMethod;
use Roave\BetterReflection\Reflection\Adapter\Exception\NotImplemented;
use Roave\BetterReflection\Reflection\Adapter\ReflectionAttribute as ReflectionAttributeAdapter;
use Roave\BetterReflection\Reflection\Adapter\ReflectionClass as ReflectionClassAdapter;
use Roave\BetterReflection\Reflection\Adapter\ReflectionMethod as ReflectionMethodAdapter;
use Roave\BetterReflection\Reflection\Adapter\ReflectionNamedType as ReflectionNamedTypeAdapter;
use Roave\BetterReflection\Reflection\Adapter\ReflectionParameter as ReflectionParameterAdapter;
use Roave\BetterReflection\Reflection\Exception\CodeLocationMissing;
use Roave\BetterReflection\Reflection\Exception\MethodPrototypeNotFound;
use Roave\BetterReflection\Reflection\Exception\NoObjectProvided;
use Roave\BetterReflection\Reflection\Exception\ObjectNotInstanceOfClass;
use Roave\BetterReflection\Reflection\ReflectionAttribute as BetterReflectionAttribute;
use Roave\BetterReflection\Reflection\ReflectionClass as BetterReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionMethod as BetterReflectionMethod;
use Roave\BetterReflection\Reflection\ReflectionNamedType as BetterReflectionNamedType;
use Roave\BetterReflection\Reflection\ReflectionParameter as BetterReflectionParameter;
use Roave\BetterReflection\Util\FileHelper;
use stdClass;
use ValueError;

use function array_combine;
use function array_map;
use function get_class_methods;

#[CoversClass(ReflectionMethodAdapter::class)]
class ReflectionMethodTest extends TestCase
{
    /** @return array<string, array{0: string}> */
    public static function coreReflectionMethodNamesProvider(): array
    {
        $methods = get_class_methods(CoreReflectionMethod::class);

        return array_combine($methods, array_map(static fn (string $i): array => [$i], $methods));
    }

    #[DataProvider('coreReflectionMethodNamesProvider')]
    public function testCoreReflectionMethods(string $methodName): void
    {
        $reflectionMethodAdapterReflection = new CoreReflectionClass(ReflectionMethodAdapter::class);

        self::assertTrue($reflectionMethodAdapterReflection->hasMethod($methodName));
        self::assertSame(ReflectionMethodAdapter::class, $reflectionMethodAdapterReflection->getMethod($methodName)->getDeclaringClass()->getName());
    }

    /** @return list<array{0: string, 1: list<mixed>, 2: mixed, 3: string|null, 4: mixed}> */
    public static function methodExpectationProvider(): array
    {
        return [
            // Inherited
            ['__toString', [], 'string', null, 'string'],
            ['inNamespace', [], true, null, true],
            ['isClosure', [], true, null, true],
            ['isDeprecated', [], true, null, true],
            ['isInternal', [], true, null, true],
            ['isUserDefined', [], true, null, true],
            ['getClosureThis', [], null, NotImplemented::class, null],
            ['getClosureScopeClass', [], null, NotImplemented::class, null],
            ['getClosureCalledClass', [], null, NotImplemented::class, null],
            ['getDocComment', [], null, null, false],
            ['getStartLine', [], 123, null, 123],
            ['getEndLine', [], 123, null, 123],
            ['getExtension', [], null, NotImplemented::class, null],
            ['getExtensionName', [], null, null, null],
            ['getFileName', [], 'filename', null, 'filename'],
            ['getName', [], 'name', null, 'name'],
            ['getNamespaceName', [], 'namespaceName', null, 'namespaceName'],
            ['getNumberOfParameters', [], 123, null, 123],
            ['getNumberOfRequiredParameters', [], 123, null, 123],
            ['getParameters', [], [], null, null],
            ['hasReturnType', [], true, null, true],
            ['getReturnType', [], null, null, null],
            ['getShortName', [], 'shortName', null, 'shortName'],
            ['getStaticVariables', [], null, NotImplemented::class, null],
            ['returnsReference', [], true, null, true],
            ['isGenerator', [], true, null, true],
            ['isVariadic', [], true, null, true],
            ['getAttributes', [], [], null, null],
            ['hasTentativeReturnType', [], false, null, false],
            ['getTentativeReturnType', [], null, null, null],
            ['getClosureUsedVariables', [], null, NotImplemented::class, null],

            // ReflectionMethod
            ['isPublic', [], true, null, true],
            ['isPrivate', [], true, null, true],
            ['isProtected', [], true, null, true],
            ['isAbstract', [], true, null, true],
            ['isFinal', [], true, null, true],
            ['isStatic', [], true, null, true],
            ['isConstructor', [], true, null, true],
            ['isDestructor', [], true, null, true],
            ['getModifiers', [], 123, null, 123],
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
        $reflectionStub = $this->createMock(BetterReflectionMethod::class);

        if ($expectedException === null) {
            $reflectionStub->expects($this->once())
                ->method($methodName)
                ->with(...$args)
                ->willReturn($returnValue);
        }

        $adapter = new ReflectionMethodAdapter($reflectionStub);

        if ($expectedException !== null) {
            $this->expectException($expectedException);
        }

        $actualReturnValue = $adapter->{$methodName}(...$args);

        if ($expectedReturnValue === null) {
            return;
        }

        self::assertSame($expectedReturnValue, $actualReturnValue);
    }

    public function testCreateFromMethodName(): void
    {
        $reflectionMethodAdapter = ReflectionMethodAdapter::createFromMethodName('ReflectionMethod::getName');

        self::assertInstanceOf(ReflectionMethodAdapter::class, $reflectionMethodAdapter);
    }

    public function testCreateFromMethodNameWithInvalidArgument(): void
    {
        self::expectException(CoreReflectionException::class);
        self::expectExceptionMessage('Argument #1 ($method) must be a valid method name');

        ReflectionMethodAdapter::createFromMethodName('InvalidName');
    }

    public function testCreateFromMethodNameWithEmptyMethodName(): void
    {
        self::expectException(CoreReflectionException::class);
        self::expectExceptionMessage('Method SomeClass::() does not exist');

        ReflectionMethodAdapter::createFromMethodName('SomeClass::');
    }

    public function testCreateFromMethodNameWhenClassDoesNotExist(): void
    {
        self::expectException(CoreReflectionException::class);
        self::expectExceptionMessage('Class "ClassDoesNotExist" does not exist');

        ReflectionMethodAdapter::createFromMethodName('ClassDoesNotExist::method');
    }

    public function testCreateFromMethodNameWhenMethodDoesNotExist(): void
    {
        self::expectException(CoreReflectionException::class);
        self::expectExceptionMessage('Method ReflectionMethod::doesNotExist() does not exist');

        ReflectionMethodAdapter::createFromMethodName('ReflectionMethod::doesNotExist');
    }

    public function testCreateFromMethodNameWithInvalidMethodName(): void
    {
        self::expectException(CoreReflectionException::class);
        self::expectExceptionMessage('Method ReflectionMethod::invalid::name() does not exist');

        ReflectionMethodAdapter::createFromMethodName('ReflectionMethod::invalid::name');
    }

    public function testGetFileNameReturnsFalseWhenNoFileName(): void
    {
        $betterReflectionMethod = $this->createMock(BetterReflectionMethod::class);
        $betterReflectionMethod
            ->method('getFileName')
            ->willReturn(null);

        $betterReflectionMethod = new ReflectionMethodAdapter($betterReflectionMethod);

        self::assertFalse($betterReflectionMethod->getFileName());
    }

    public function testGetFileNameReturnsPathWithSystemDirectorySeparator(): void
    {
        $fileName = 'foo/bar\\foo/bar.php';

        $betterReflectionMethod = $this->createMock(BetterReflectionMethod::class);
        $betterReflectionMethod
            ->method('getFileName')
            ->willReturn($fileName);

        $betterReflectionMethod = new ReflectionMethodAdapter($betterReflectionMethod);

        self::assertSame(FileHelper::normalizeSystemPath($fileName), $betterReflectionMethod->getFileName());
    }

    public function testGetDocCommentReturnsFalseWhenNoDocComment(): void
    {
        $betterReflectionMethod = $this->createMock(BetterReflectionMethod::class);
        $betterReflectionMethod
            ->method('getDocComment')
            ->willReturn(null);

        $reflectionMethodAdapter = new ReflectionMethodAdapter($betterReflectionMethod);

        self::assertFalse($reflectionMethodAdapter->getDocComment());
    }

    public function testGetDeclaringClass(): void
    {
        $betterReflectionClass = $this->createMock(BetterReflectionClass::class);
        $betterReflectionClass
            ->method('getName')
            ->willReturn('DeclaringClass');

        $betterReflectionMethod = $this->createMock(BetterReflectionMethod::class);
        $betterReflectionMethod
            ->method('getImplementingClass')
            ->willReturn($betterReflectionClass);

        $reflectionMethodAdapter = new ReflectionMethodAdapter($betterReflectionMethod);

        self::assertInstanceOf(ReflectionClassAdapter::class, $reflectionMethodAdapter->getDeclaringClass());
        self::assertSame('DeclaringClass', $reflectionMethodAdapter->getDeclaringClass()->getName());
    }

    public function testGetExtensionNameReturnsFalseWhenNoExtensionName(): void
    {
        $betterReflectionMethod = $this->createMock(BetterReflectionMethod::class);
        $betterReflectionMethod
            ->method('getExtensionName')
            ->willReturn(null);

        $betterReflectionMethod = new ReflectionMethodAdapter($betterReflectionMethod);

        self::assertFalse($betterReflectionMethod->getExtensionName());
    }

    public function testGetReturnType(): void
    {
        $betterReflectionMethod = $this->createMock(BetterReflectionMethod::class);
        $betterReflectionMethod
            ->method('getReturnType')
            ->willReturn($this->createMock(BetterReflectionNamedType::class));

        $reflectionMethodAdapter = new ReflectionMethodAdapter($betterReflectionMethod);

        self::assertInstanceOf(ReflectionNamedTypeAdapter::class, $reflectionMethodAdapter->getReturnType());
    }

    public function testGetClosure(): void
    {
        $closure = static function (): void {
        };

        $betterReflectionMethod = $this->createMock(BetterReflectionMethod::class);
        $betterReflectionMethod
            ->method('getClosure')
            ->willReturn($closure);

        $reflectionMethodAdapter = new ReflectionMethodAdapter($betterReflectionMethod);

        self::assertInstanceOf(Closure::class, $reflectionMethodAdapter->getClosure());
    }

    public function testGetClosureReturnsNullWhenNoObject(): void
    {
        $this->expectException(ValueError::class);

        $betterReflectionMethod = $this->createMock(BetterReflectionMethod::class);
        $betterReflectionMethod
            ->method('getClosure')
            ->willThrowException(NoObjectProvided::create());

        $reflectionMethodAdapter = new ReflectionMethodAdapter($betterReflectionMethod);

        $reflectionMethodAdapter->getClosure();
    }

    public function testGetClosureThrowsExceptionWhenObjectNotInstanceOfClass(): void
    {
        $betterReflectionMethod = $this->createMock(BetterReflectionMethod::class);
        $betterReflectionMethod
            ->method('getClosure')
            ->willThrowException(ObjectNotInstanceOfClass::fromClassName('Foo'));

        $reflectionMethodAdapter = new ReflectionMethodAdapter($betterReflectionMethod);

        $this->expectException(CoreReflectionException::class);
        $reflectionMethodAdapter->getClosure(new stdClass());
    }

    public function testInvoke(): void
    {
        $betterReflectionMethod = $this->createMock(BetterReflectionMethod::class);
        $betterReflectionMethod
            ->method('isPublic')
            ->willReturn(true);
        $betterReflectionMethod
            ->method('invoke')
            ->with(null, 100, 23)
            ->willReturn(123);

        $reflectionMethodAdapter = new ReflectionMethodAdapter($betterReflectionMethod);

        self::assertSame(123, $reflectionMethodAdapter->invoke(null, 100, 23));
    }

    public function testInvokeArgs(): void
    {
        $betterReflectionMethod = $this->createMock(BetterReflectionMethod::class);
        $betterReflectionMethod
            ->method('isPublic')
            ->willReturn(true);
        $betterReflectionMethod
            ->method('invokeArgs')
            ->with(null, [100, 23])
            ->willReturn(123);

        $reflectionMethodAdapter = new ReflectionMethodAdapter($betterReflectionMethod);

        self::assertSame(123, $reflectionMethodAdapter->invokeArgs(null, [100, 23]));
    }

    public function testInvokeReturnsNullWhenNoObject(): void
    {
        $betterReflectionMethod = $this->createMock(BetterReflectionMethod::class);
        $betterReflectionMethod
            ->method('isPublic')
            ->willReturn(true);
        $betterReflectionMethod
            ->method('invoke')
            ->willThrowException(NoObjectProvided::create());

        $reflectionMethodAdapter = new ReflectionMethodAdapter($betterReflectionMethod);

        self::assertNull($reflectionMethodAdapter->invoke(null));
    }

    public function testInvokeArgsReturnsNullWhenNoObject(): void
    {
        $betterReflectionMethod = $this->createMock(BetterReflectionMethod::class);
        $betterReflectionMethod
            ->method('isPublic')
            ->willReturn(true);
        $betterReflectionMethod
            ->method('invokeArgs')
            ->willThrowException(NoObjectProvided::create());

        $reflectionMethodAdapter = new ReflectionMethodAdapter($betterReflectionMethod);

        self::assertNull($reflectionMethodAdapter->invokeArgs(null, []));
    }

    public function testInvokeThrowsExceptionWhenObjectNotInstanceOfClass(): void
    {
        $betterReflectionMethod = $this->createMock(BetterReflectionMethod::class);
        $betterReflectionMethod
            ->method('isPublic')
            ->willReturn(true);
        $betterReflectionMethod
            ->method('invoke')
            ->willThrowException(ObjectNotInstanceOfClass::fromClassName('Foo'));

        $reflectionMethodAdapter = new ReflectionMethodAdapter($betterReflectionMethod);

        $this->expectException(CoreReflectionException::class);
        $reflectionMethodAdapter->invoke(new stdClass());
    }

    public function testInvokeArgsThrowsExceptionWhenObjectNotInstanceOfClass(): void
    {
        $betterReflectionMethod = $this->createMock(BetterReflectionMethod::class);
        $betterReflectionMethod
            ->method('isPublic')
            ->willReturn(true);
        $betterReflectionMethod
            ->method('invokeArgs')
            ->willThrowException(ObjectNotInstanceOfClass::fromClassName('Foo'));

        $reflectionMethodAdapter = new ReflectionMethodAdapter($betterReflectionMethod);

        $this->expectException(CoreReflectionException::class);
        $reflectionMethodAdapter->invokeArgs(new stdClass(), []);
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

        $betterReflectionMethod = $this->createMock(BetterReflectionMethod::class);
        $betterReflectionMethod
            ->method('getAttributes')
            ->willReturn($betterReflectionAttributes);

        $reflectionMethodAdapter = new ReflectionMethodAdapter($betterReflectionMethod);
        $attributes              = $reflectionMethodAdapter->getAttributes();

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

        $betterReflectionMethod = $this->getMockBuilder(BetterReflectionMethod::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAttributes'])
            ->getMock();

        $betterReflectionMethod
            ->method('getAttributes')
            ->willReturn($betterReflectionAttributes);

        $reflectionMethodAdapter = new ReflectionMethodAdapter($betterReflectionMethod);
        $attributes              = $reflectionMethodAdapter->getAttributes($someAttributeClassName);

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

        $betterReflectionMethod = $this->getMockBuilder(BetterReflectionMethod::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAttributes'])
            ->getMock();

        $betterReflectionMethod
            ->method('getAttributes')
            ->willReturn($betterReflectionAttributes);

        $reflectionMethodAdapter = new ReflectionMethodAdapter($betterReflectionMethod);

        self::assertCount(1, $reflectionMethodAdapter->getAttributes($className, ReflectionAttributeAdapter::IS_INSTANCEOF));
        self::assertCount(2, $reflectionMethodAdapter->getAttributes($parentClassName, ReflectionAttributeAdapter::IS_INSTANCEOF));
        self::assertCount(2, $reflectionMethodAdapter->getAttributes($interfaceName, ReflectionAttributeAdapter::IS_INSTANCEOF));
    }

    public function testGetAttributesThrowsExceptionForInvalidFlags(): void
    {
        $betterReflectionMethod  = $this->createMock(BetterReflectionMethod::class);
        $reflectionMethodAdapter = new ReflectionMethodAdapter($betterReflectionMethod);

        $this->expectException(ValueError::class);
        $reflectionMethodAdapter->getAttributes(null, 123);
    }

    public function testPropertyName(): void
    {
        $betterReflectionMethod = $this->createMock(BetterReflectionMethod::class);
        $betterReflectionMethod
            ->method('getName')
            ->willReturn('foo');

        $reflectionMethodAdapter = new ReflectionMethodAdapter($betterReflectionMethod);
        self::assertSame('foo', $reflectionMethodAdapter->name);
    }

    public function testPropertyClass(): void
    {
        $betterReflectionClass = $this->createMock(BetterReflectionClass::class);
        $betterReflectionClass
            ->method('getName')
            ->willReturn('Foo');

        $betterReflectionMethod = $this->createMock(BetterReflectionMethod::class);
        $betterReflectionMethod
            ->method('getImplementingClass')
            ->willReturn($betterReflectionClass);

        $reflectionMethodAdapter = new ReflectionMethodAdapter($betterReflectionMethod);
        self::assertSame('Foo', $reflectionMethodAdapter->class);
    }

    public function testUnknownProperty(): void
    {
        $betterReflectionMethod  = $this->createMock(BetterReflectionMethod::class);
        $reflectionMethodAdapter = new ReflectionMethodAdapter($betterReflectionMethod);
        $this->expectException(OutOfBoundsException::class);
        $this->expectExceptionMessage('Property Roave\BetterReflection\Reflection\Adapter\ReflectionMethod::$foo does not exist.');
        /** @phpstan-ignore-next-line */
        $reflectionMethodAdapter->foo;
    }

    public function testHasPrototypeReturnsTrueWhenPrototypeExists(): void
    {
        $betterReflectionMethod = $this->createMock(BetterReflectionMethod::class);
        $betterReflectionMethod
            ->method('getPrototype')
            ->willReturn($this->createMock(BetterReflectionMethod::class));

        $reflectionMethodAdapter = new ReflectionMethodAdapter($betterReflectionMethod);

        self::assertTrue($reflectionMethodAdapter->hasPrototype());
    }

    public function testHasPrototypeReturnsFalseWhenNoPrototype(): void
    {
        $betterReflectionMethod = $this->createMock(BetterReflectionMethod::class);
        $betterReflectionMethod
            ->method('getPrototype')
            ->willThrowException(new MethodPrototypeNotFound());

        $reflectionMethodAdapter = new ReflectionMethodAdapter($betterReflectionMethod);

        self::assertFalse($reflectionMethodAdapter->hasPrototype());
    }

    public function testGetPrototype(): void
    {
        $betterReflectionMethod = $this->createMock(BetterReflectionMethod::class);
        $betterReflectionMethod
            ->method('getPrototype')
            ->willReturn($this->createMock(BetterReflectionMethod::class));

        $reflectionMethodAdapter = new ReflectionMethodAdapter($betterReflectionMethod);

        self::assertInstanceOf(ReflectionMethodAdapter::class, $reflectionMethodAdapter->getPrototype());
    }

    public function testGetStartLineReturnsFalseWhenLocationMissing(): void
    {
        $betterReflectionMethod = $this->createMock(BetterReflectionMethod::class);
        $betterReflectionMethod
            ->method('getStartLine')
            ->willThrowException(new CodeLocationMissing());

        $reflectionMethodAdapter = new ReflectionMethodAdapter($betterReflectionMethod);

        self::assertFalse($reflectionMethodAdapter->getStartLine());
    }

    public function testGetEndLineReturnsFalseWhenLocationMissing(): void
    {
        $betterReflectionMethod = $this->createMock(BetterReflectionMethod::class);
        $betterReflectionMethod
            ->method('getEndLine')
            ->willThrowException(new CodeLocationMissing());

        $reflectionMethodAdapter = new ReflectionMethodAdapter($betterReflectionMethod);

        self::assertFalse($reflectionMethodAdapter->getEndLine());
    }

    public function testGetParameters(): void
    {
        $betterReflectionMethod = $this->createMock(BetterReflectionMethod::class);
        $betterReflectionMethod
            ->method('getParameters')
            ->willReturn([$this->createMock(BetterReflectionParameter::class)]);

        $reflectionMethodAdapter = new ReflectionMethodAdapter($betterReflectionMethod);

        self::assertCount(1, $reflectionMethodAdapter->getParameters());
        self::assertContainsOnlyInstancesOf(ReflectionParameterAdapter::class, $reflectionMethodAdapter->getParameters());
    }
}
