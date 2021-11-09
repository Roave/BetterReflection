<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection\Adapter;

use Closure;
use Exception;
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

use function array_combine;
use function array_map;
use function get_class_methods;
use function is_array;

/**
 * @covers \Roave\BetterReflection\Reflection\Adapter\ReflectionFunction
 */
class ReflectionFunctionTest extends TestCase
{
    public function coreReflectionMethodNamesProvider(): array
    {
        $methods = get_class_methods(CoreReflectionFunction::class);

        return array_combine($methods, array_map(static fn (string $i): array => [$i], $methods));
    }

    /**
     * @dataProvider coreReflectionMethodNamesProvider
     */
    public function testCoreReflectionMethods(string $methodName): void
    {
        $reflectionFunctionAdapterReflection = new CoreReflectionClass(ReflectionFunctionAdapter::class);

        self::assertTrue($reflectionFunctionAdapterReflection->hasMethod($methodName));
        self::assertSame(ReflectionFunctionAdapter::class, $reflectionFunctionAdapterReflection->getMethod($methodName)->getDeclaringClass()->getName());
    }

    public function methodExpectationProvider(): array
    {
        $mockParameter = $this->createMock(BetterReflectionParameter::class);

        $mockType = $this->createMock(BetterReflectionNamedType::class);

        $mockAttribute = $this->createMock(BetterReflectionAttribute::class);

        $closure = static function (): void {
        };

        return [
            // Inherited
            ['__toString', [], 'string', null, 'string', null],
            ['inNamespace', [], true, null, true, null],
            ['isClosure', [], true, null, true, null],
            ['isDeprecated', [], true, null, true, null],
            ['isInternal', [], true, null, true, null],
            ['isUserDefined', [], true, null, true, null],
            ['getClosureThis', [], null, NotImplemented::class, null, null],
            ['getClosureScopeClass', [], null, NotImplemented::class, null, null],
            ['getDocComment', [], '', null, false, null],
            ['getStartLine', [], 123, null, 123, null],
            ['getEndLine', [], 123, null, 123, null],
            ['getExtension', [], null, NotImplemented::class, null, null],
            ['getExtensionName', [], null, null, null, null],
            ['getFileName', [], 'filename', null, 'filename', null],
            ['getName', [], 'name', null, 'name', null],
            ['getNamespaceName', [], 'namespaceName', null, 'namespaceName', null],
            ['getNumberOfParameters', [], 123, null, 123, null],
            ['getNumberOfRequiredParameters', [], 123, null, 123, null],
            ['getParameters', [], [$mockParameter], null, null, ReflectionParameterAdapter::class],
            ['hasReturnType', [], true, null, true, null],
            ['getReturnType', [], $mockType, null, null, ReflectionNamedTypeAdapter::class],
            ['getShortName', [], 'shortName', null, 'shortName', null],
            ['getStaticVariables', [], null, NotImplemented::class, null, null],
            ['returnsReference', [], true, null, true, null],
            ['isGenerator', [], true, null, true, null],
            ['isVariadic', [], true, null, true, null],
            ['getAttributes', [], [$mockAttribute], null, null, ReflectionAttributeAdapter::class],
            ['hasTentativeReturnType', [], false, null, false, null],
            ['getTentativeReturnType', [], null, null, null, null],
            ['getClosureUsedVariables', [], null, NotImplemented::class, null, null],

            // ReflectionFunction
            ['isDisabled', [], false, null, false, null],
            ['invoke', [], null, null, null, null],
            ['invokeArgs', [[]], null, null, null, null],
            ['getClosure', [], $closure, null, $closure, Closure::class],
            ['isStatic', [], true, null, true, null],
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
            ->willReturn('');

        $reflectionFunctionAdapter = new ReflectionFunctionAdapter($betterReflectionFunction);

        self::assertFalse($reflectionFunctionAdapter->getDocComment());
    }

    public function testGetExtensionNameReturnsEmptyStringWhenNoExtensionName(): void
    {
        $betterReflectionFunction = $this->createMock(BetterReflectionFunction::class);
        $betterReflectionFunction
            ->method('getExtensionName')
            ->willReturn('');

        $betterReflectionFunction = new ReflectionFunctionAdapter($betterReflectionFunction);

        self::assertSame('', $betterReflectionFunction->getExtensionName());
    }

    public function testGetClosureReturnsNullWhenError(): void
    {
        $betterReflectionFunction = $this->createMock(BetterReflectionFunction::class);
        $betterReflectionFunction
            ->method('getClosure')
            ->willThrowException(new Exception());

        $betterReflectionFunction = new ReflectionFunctionAdapter($betterReflectionFunction);

        self::expectException(Throwable::class);

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
        $betterReflectionAttribute1 = $this->createMock(BetterReflectionAttribute::class);
        $betterReflectionAttribute1
            ->method('getName')
            ->willReturn('SomeAttribute');
        $betterReflectionAttribute2 = $this->createMock(BetterReflectionAttribute::class);
        $betterReflectionAttribute2
            ->method('getName')
            ->willReturn('AnotherAttribute');

        $betterReflectionAttributes = [$betterReflectionAttribute1, $betterReflectionAttribute2];

        $betterReflectionFunction = $this->getMockBuilder(BetterReflectionFunction::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAttributes'])
            ->getMock();

        $betterReflectionFunction
            ->method('getAttributes')
            ->willReturn($betterReflectionAttributes);

        $reflectionFunctionAdapter = new ReflectionFunctionAdapter($betterReflectionFunction);
        $attributes                = $reflectionFunctionAdapter->getAttributes('SomeAttribute');

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

        $betterReflectionFunction = $this->getMockBuilder(BetterReflectionFunction::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAttributes'])
            ->getMock();

        $betterReflectionFunction
            ->method('getAttributes')
            ->willReturn($betterReflectionAttributes);

        $reflectionFunctionAdapter = new ReflectionFunctionAdapter($betterReflectionFunction);

        self::assertCount(1, $reflectionFunctionAdapter->getAttributes('ClassName', ReflectionAttributeAdapter::IS_INSTANCEOF));
        self::assertCount(2, $reflectionFunctionAdapter->getAttributes('ParentClassName', ReflectionAttributeAdapter::IS_INSTANCEOF));
        self::assertCount(2, $reflectionFunctionAdapter->getAttributes('InterfaceName', ReflectionAttributeAdapter::IS_INSTANCEOF));
    }
}
