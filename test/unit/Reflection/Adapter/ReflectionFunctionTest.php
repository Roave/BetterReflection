<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection\Adapter;

use Closure;
use Exception;
use OutOfBoundsException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass as CoreReflectionClass;
use ReflectionException as CoreReflectionException;
use ReflectionFunction as CoreReflectionFunction;
use Roave\BetterReflection\Reflection\Adapter\Exception\NotImplemented;
use Roave\BetterReflection\Reflection\Adapter\ReflectionAttribute as ReflectionAttributeAdapter;
use Roave\BetterReflection\Reflection\Adapter\ReflectionFunction as ReflectionFunctionAdapter;
use Roave\BetterReflection\Reflection\Adapter\ReflectionNamedType as ReflectionNamedTypeAdapter;
use Roave\BetterReflection\Reflection\Adapter\ReflectionParameter as ReflectionParameterAdapter;
use Roave\BetterReflection\Reflection\ReflectionAttribute as BetterReflectionAttribute;
use Roave\BetterReflection\Reflection\ReflectionClass as BetterReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionFunction as BetterReflectionFunction;
use Roave\BetterReflection\Reflection\ReflectionNamedType as BetterReflectionNamedType;
use Roave\BetterReflection\Reflection\ReflectionParameter as BetterReflectionParameter;
use Roave\BetterReflection\Util\FileHelper;
use Throwable;
use ValueError;

use function array_combine;
use function array_map;
use function get_class_methods;

#[CoversClass(ReflectionFunctionAdapter::class)]
class ReflectionFunctionTest extends TestCase
{
    /** @return array<string, array{0: string}> */
    public static function coreReflectionMethodNamesProvider(): array
    {
        $methods = get_class_methods(CoreReflectionFunction::class);

        return array_combine($methods, array_map(static fn (string $i): array => [$i], $methods));
    }

    /** @dataProvider coreReflectionMethodNamesProvider */
    public function testCoreReflectionMethods(string $methodName): void
    {
        $reflectionFunctionAdapterReflection = new CoreReflectionClass(ReflectionFunctionAdapter::class);

        self::assertTrue($reflectionFunctionAdapterReflection->hasMethod($methodName));
        self::assertSame(ReflectionFunctionAdapter::class, $reflectionFunctionAdapterReflection->getMethod($methodName)->getDeclaringClass()->getName());
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

            // ReflectionFunction
            ['isDisabled', [], false, null, false],
            ['invoke', [], null, null, null],
            ['invokeArgs', [[]], null, null, null],
            ['isStatic', [], true, null, true],
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
        $reflectionStub = $this->createMock(BetterReflectionFunction::class);

        if ($expectedException === null) {
            $reflectionStub->expects($this->once())
                ->method($methodName)
                ->with(...$args)
                ->willReturn($returnValue);
        }

        $adapter = new ReflectionFunctionAdapter($reflectionStub);

        if ($expectedException !== null) {
            $this->expectException($expectedException);
        }

        $actualReturnValue = $adapter->{$methodName}(...$args);

        if ($expectedReturnValue === null) {
            return;
        }

        self::assertSame($expectedReturnValue, $actualReturnValue);
    }

    public function testGetFileNameReturnsFalseWhenNoFileName(): void
    {
        $betterReflectionFunction = $this->createMock(BetterReflectionFunction::class);
        $betterReflectionFunction
            ->method('getFileName')
            ->willReturn(null);

        $betterReflectionFunction = new ReflectionFunctionAdapter($betterReflectionFunction);

        self::assertFalse($betterReflectionFunction->getFileName());
    }

    public function testGetFileNameReturnsPathWithSystemDirectorySeparator(): void
    {
        $fileName = 'foo/bar\\foo/bar.php';

        $betterReflectionFunction = $this->createMock(BetterReflectionFunction::class);
        $betterReflectionFunction
            ->method('getFileName')
            ->willReturn($fileName);

        $betterReflectionFunction = new ReflectionFunctionAdapter($betterReflectionFunction);

        self::assertSame(FileHelper::normalizeSystemPath($fileName), $betterReflectionFunction->getFileName());
    }

    public function testGetDocCommentReturnsFalseWhenNoDocComment(): void
    {
        $betterReflectionFunction = $this->createMock(BetterReflectionFunction::class);
        $betterReflectionFunction
            ->method('getDocComment')
            ->willReturn(null);

        $reflectionFunctionAdapter = new ReflectionFunctionAdapter($betterReflectionFunction);

        self::assertFalse($reflectionFunctionAdapter->getDocComment());
    }

    public function testGetExtensionNameReturnsFalseWhenNoExtensionName(): void
    {
        $betterReflectionFunction = $this->createMock(BetterReflectionFunction::class);
        $betterReflectionFunction
            ->method('getExtensionName')
            ->willReturn(null);

        $betterReflectionFunction = new ReflectionFunctionAdapter($betterReflectionFunction);

        self::assertFalse($betterReflectionFunction->getExtensionName());
    }

    public function testGetReturnType(): void
    {
        $betterReflectionFunction = $this->createMock(BetterReflectionFunction::class);
        $betterReflectionFunction
            ->method('getReturnType')
            ->willReturn($this->createMock(BetterReflectionNamedType::class));

        $betterReflectionFunction = new ReflectionFunctionAdapter($betterReflectionFunction);

        self::assertInstanceOf(ReflectionNamedTypeAdapter::class, $betterReflectionFunction->getReturnType());
    }

    public function testGetClosure(): void
    {
        $closure = static function (): void {
        };

        $betterReflectionFunction = $this->createMock(BetterReflectionFunction::class);
        $betterReflectionFunction
            ->method('getClosure')
            ->willReturn($closure);

        $betterReflectionFunction = new ReflectionFunctionAdapter($betterReflectionFunction);

        self::assertInstanceOf(Closure::class, $betterReflectionFunction->getClosure());
    }

    public function testGetClosureReturnsNullWhenError(): void
    {
        $betterReflectionFunction = $this->createMock(BetterReflectionFunction::class);
        $betterReflectionFunction
            ->method('getClosure')
            ->willThrowException(new Exception());

        $betterReflectionFunction = new ReflectionFunctionAdapter($betterReflectionFunction);

        $this->expectException(Throwable::class);

        $betterReflectionFunction->getClosure();
    }

    public function testInvokeThrowsExceptionWhenError(): void
    {
        $betterReflectionFunction = $this->createMock(BetterReflectionFunction::class);
        $betterReflectionFunction
            ->method('invoke')
            ->willThrowException(new Exception());

        $betterReflectionFunction = new ReflectionFunctionAdapter($betterReflectionFunction);

        $this->expectException(CoreReflectionException::class);
        $betterReflectionFunction->invoke();
    }

    public function testInvokeArgsThrowsExceptionWhenError(): void
    {
        $betterReflectionFunction = $this->createMock(BetterReflectionFunction::class);
        $betterReflectionFunction
            ->method('invokeArgs')
            ->willThrowException(new Exception());

        $betterReflectionFunction = new ReflectionFunctionAdapter($betterReflectionFunction);

        $this->expectException(CoreReflectionException::class);
        $betterReflectionFunction->invokeArgs([]);
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

        $betterReflectionFunction = $this->createMock(BetterReflectionFunction::class);
        $betterReflectionFunction
            ->method('getAttributes')
            ->willReturn($betterReflectionAttributes);

        $reflectionFunctionAdapter = new ReflectionFunctionAdapter($betterReflectionFunction);
        $attributes                = $reflectionFunctionAdapter->getAttributes();

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

        $betterReflectionFunction = $this->getMockBuilder(BetterReflectionFunction::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAttributes'])
            ->getMock();

        $betterReflectionFunction
            ->method('getAttributes')
            ->willReturn($betterReflectionAttributes);

        $reflectionFunctionAdapter = new ReflectionFunctionAdapter($betterReflectionFunction);
        $attributes                = $reflectionFunctionAdapter->getAttributes($someAttributeClassName);

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

        $betterReflectionFunction = $this->getMockBuilder(BetterReflectionFunction::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAttributes'])
            ->getMock();

        $betterReflectionFunction
            ->method('getAttributes')
            ->willReturn($betterReflectionAttributes);

        $reflectionFunctionAdapter = new ReflectionFunctionAdapter($betterReflectionFunction);

        self::assertCount(1, $reflectionFunctionAdapter->getAttributes($className, ReflectionAttributeAdapter::IS_INSTANCEOF));
        self::assertCount(2, $reflectionFunctionAdapter->getAttributes($parentClassName, ReflectionAttributeAdapter::IS_INSTANCEOF));
        self::assertCount(2, $reflectionFunctionAdapter->getAttributes($interfaceName, ReflectionAttributeAdapter::IS_INSTANCEOF));
    }

    public function testGetAttributesThrowsExceptionForInvalidFlags(): void
    {
        $betterReflectionFunction  = $this->createMock(BetterReflectionFunction::class);
        $reflectionFunctionAdapter = new ReflectionFunctionAdapter($betterReflectionFunction);

        $this->expectException(ValueError::class);
        $reflectionFunctionAdapter->getAttributes(null, 123);
    }

    public function testPropertyName(): void
    {
        $betterReflectionFunction = $this->createMock(BetterReflectionFunction::class);
        $betterReflectionFunction
            ->method('getName')
            ->willReturn('foo');

        $reflectionFunctionAdapter = new ReflectionFunctionAdapter($betterReflectionFunction);
        self::assertSame('foo', $reflectionFunctionAdapter->name);
    }

    public function testUnknownProperty(): void
    {
        $betterReflectionFunction  = $this->createMock(BetterReflectionFunction::class);
        $reflectionFunctionAdapter = new ReflectionFunctionAdapter($betterReflectionFunction);
        $this->expectException(OutOfBoundsException::class);
        $this->expectExceptionMessage('Property Roave\BetterReflection\Reflection\Adapter\ReflectionFunction::$foo does not exist.');
        /** @phpstan-ignore-next-line */
        $reflectionFunctionAdapter->foo;
    }

    public function testIsAnonymous(): void
    {
        $betterReflectionFunction = $this->createMock(BetterReflectionFunction::class);
        $betterReflectionFunction
            ->method('isClosure')
            ->willReturn(true);

        $reflectionFunctionAdapter = new ReflectionFunctionAdapter($betterReflectionFunction);

        self::assertTrue($reflectionFunctionAdapter->isAnonymous());
    }

    public function testGetParameters(): void
    {
        $betterReflectionFunction = $this->createMock(BetterReflectionFunction::class);
        $betterReflectionFunction
            ->method('getParameters')
            ->willReturn([$this->createMock(BetterReflectionParameter::class)]);

        $reflectionFunctionAdapter = new ReflectionFunctionAdapter($betterReflectionFunction);

        self::assertCount(1, $reflectionFunctionAdapter->getParameters());
        self::assertContainsOnlyInstancesOf(ReflectionParameterAdapter::class, $reflectionFunctionAdapter->getParameters());
    }
}
