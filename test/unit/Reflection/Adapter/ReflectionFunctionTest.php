<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection\Adapter;

use Exception;
use PHPUnit\Framework\TestCase;
use ReflectionClass as CoreReflectionClass;
use ReflectionException as CoreReflectionException;
use ReflectionFunction as CoreReflectionFunction;
use Roave\BetterReflection\Reflection\Adapter\Exception\NotImplemented;
use Roave\BetterReflection\Reflection\Adapter\ReflectionFunction as ReflectionFunctionAdapter;
use Roave\BetterReflection\Reflection\ReflectionFunction as BetterReflectionFunction;
use Roave\BetterReflection\Reflection\ReflectionParameter as BetterReflectionParameter;
use Roave\BetterReflection\Reflection\ReflectionType as BetterReflectionType;
use Roave\BetterReflection\Util\FileHelper;

use function array_combine;
use function array_map;
use function get_class_methods;

/**
 * @covers \Roave\BetterReflection\Reflection\Adapter\ReflectionFunction
 */
class ReflectionFunctionTest extends TestCase
{
    public function coreReflectionMethodNamesProvider(): array
    {
        $methods = get_class_methods(CoreReflectionFunction::class);

        return array_combine($methods, array_map(static function (string $i): array {
            return [$i];
        }, $methods));
    }

    /**
     * @dataProvider coreReflectionMethodNamesProvider
     */
    public function testCoreReflectionMethods(string $methodName): void
    {
        $reflectionFunctionAdapterReflection = new CoreReflectionClass(ReflectionFunctionAdapter::class);
        self::assertTrue($reflectionFunctionAdapterReflection->hasMethod($methodName));
    }

    public function methodExpectationProvider(): array
    {
        $mockParameter = $this->createMock(BetterReflectionParameter::class);

        $mockType = $this->createMock(BetterReflectionType::class);

        $closure = static function (): void {
        };

        return [
            // Inherited
            ['__toString', null, '', []],
            ['inNamespace', null, true, []],
            ['isClosure', null, true, []],
            ['isDeprecated', null, true, []],
            ['isInternal', null, true, []],
            ['isUserDefined', null, true, []],
            ['getClosureThis', NotImplemented::class, null, []],
            ['getClosureScopeClass', NotImplemented::class, null, []],
            ['getDocComment', null, '', []],
            ['getStartLine', null, 123, []],
            ['getEndLine', null, 123, []],
            ['getExtension', NotImplemented::class, null, []],
            ['getExtensionName', null, null, []],
            ['getFileName', null, '', []],
            ['getName', null, '', []],
            ['getNamespaceName', null, '', []],
            ['getNumberOfParameters', null, 123, []],
            ['getNumberOfRequiredParameters', null, 123, []],
            ['getParameters', null, [$mockParameter], []],
            ['getReturnType', null, $mockType, []],
            ['getShortName', null, '', []],
            ['getStaticVariables', NotImplemented::class, null, []],
            ['returnsReference', null, true, []],
            ['isGenerator', null, true, []],
            ['isVariadic', null, true, []],

            // ReflectionFunction
            ['isDisabled', null, false, []],
            ['invoke', null, null, []],
            ['invokeArgs', null, null, [[]]],
            ['getClosure', null, $closure, []],
        ];
    }

    /**
     * @param mixed[] $args
     *
     * @dataProvider methodExpectationProvider
     */
    public function testAdapterMethods(string $methodName, ?string $expectedException, mixed $returnValue, array $args): void
    {
        $reflectionStub = $this->createMock(BetterReflectionFunction::class);

        if ($expectedException === null) {
            $reflectionStub->expects($this->once())
                ->method($methodName)
                ->with(...$args)
                ->will($this->returnValue($returnValue));
        }

        if ($expectedException !== null) {
            $this->expectException($expectedException);
        }

        $adapter = new ReflectionFunctionAdapter($reflectionStub);
        $adapter->{$methodName}(...$args);
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

    public function testGetExtensionNameReturnsFalseWhenNoExtensionName(): void
    {
        $betterReflectionFunction = $this->createMock(BetterReflectionFunction::class);
        $betterReflectionFunction
            ->method('getExtensionName')
            ->willReturn(null);

        $betterReflectionFunction = new ReflectionFunctionAdapter($betterReflectionFunction);

        self::assertFalse($betterReflectionFunction->getExtensionName());
    }

    public function testGetClosureReturnsNullWhenError(): void
    {
        $betterReflectionFunction = $this->createMock(BetterReflectionFunction::class);
        $betterReflectionFunction
            ->method('getClosure')
            ->willThrowException(new Exception());

        $betterReflectionFunction = new ReflectionFunctionAdapter($betterReflectionFunction);

        self::assertNull($betterReflectionFunction->getClosure());
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
}
