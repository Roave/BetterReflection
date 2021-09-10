<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection\Adapter;

use PHPUnit\Framework\TestCase;
use ReflectionClass as CoreReflectionClass;
use ReflectionException as CoreReflectionException;
use ReflectionMethod as CoreReflectionMethod;
use Roave\BetterReflection\Reflection\Adapter\Exception\NotImplemented;
use Roave\BetterReflection\Reflection\Adapter\ReflectionClass as ReflectionClassAdapter;
use Roave\BetterReflection\Reflection\Adapter\ReflectionMethod as ReflectionMethodAdapter;
use Roave\BetterReflection\Reflection\Adapter\ReflectionNamedType as ReflectionNamedTypeAdapter;
use Roave\BetterReflection\Reflection\Adapter\ReflectionParameter as ReflectionParameterAdapter;
use Roave\BetterReflection\Reflection\Exception\NoObjectProvided;
use Roave\BetterReflection\Reflection\Exception\ObjectNotInstanceOfClass;
use Roave\BetterReflection\Reflection\ReflectionClass as BetterReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionMethod as BetterReflectionMethod;
use Roave\BetterReflection\Reflection\ReflectionParameter as BetterReflectionParameter;
use Roave\BetterReflection\Reflection\ReflectionType as BetterReflectionType;
use Roave\BetterReflection\Util\FileHelper;
use stdClass;
use TypeError;

use function array_combine;
use function array_map;
use function get_class_methods;

/**
 * @covers \Roave\BetterReflection\Reflection\Adapter\ReflectionMethod
 */
class ReflectionMethodTest extends TestCase
{
    public function coreReflectionMethodNamesProvider(): array
    {
        $methods = get_class_methods(CoreReflectionMethod::class);

        return array_combine($methods, array_map(static function (string $i): array {
            return [$i];
        }, $methods));
    }

    /**
     * @dataProvider coreReflectionMethodNamesProvider
     */
    public function testCoreReflectionMethods(string $methodName): void
    {
        $reflectionMethodAdapterReflection = new CoreReflectionClass(ReflectionMethodAdapter::class);
        self::assertTrue($reflectionMethodAdapterReflection->hasMethod($methodName));
    }

    public function methodExpectationProvider(): array
    {
        $mockParameter = $this->createMock(BetterReflectionParameter::class);

        $mockMethod = $this->createMock(BetterReflectionMethod::class);

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

            // ReflectionMethod
            ['isPublic', null, true, []],
            ['isPrivate', null, true, []],
            ['isProtected', null, true, []],
            ['isAbstract', null, true, []],
            ['isFinal', null, true, []],
            ['isStatic', null, true, []],
            ['isConstructor', null, true, []],
            ['isDestructor', null, true, []],
            ['getClosure', null, $closure, []],
            ['getModifiers', null, 123, []],
            ['getPrototype', null, $mockMethod, []],
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
        $reflectionStub = $this->createMock(BetterReflectionMethod::class);

        if ($expectedException === null) {
            $reflectionStub->expects($this->once())
                ->method($methodName)
                ->with(...$args)
                ->will($this->returnValue($returnValue));
        }

        if ($expectedException !== null) {
            $this->expectException($expectedException);
        }

        $adapter            = new ReflectionMethodAdapter($reflectionStub);
        $adapterReturnValue = $adapter->{$methodName}(...$args);

        switch ($methodName) {
            case 'getParameters':
                self::assertContainsOnly(ReflectionParameterAdapter::class, $adapterReturnValue);
                break;

            case 'getReturnType':
                self::assertInstanceOf(ReflectionNamedTypeAdapter::class, $adapterReturnValue);
                break;

            case 'getPrototype':
                self::assertInstanceOf(ReflectionMethodAdapter::class, $adapterReturnValue);
                break;

            default:
                self::assertEquals($returnValue, $adapterReturnValue);
        }
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
            ->willReturn('');

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

    public function testGetClosureReturnsNullWhenNoObject(): void
    {
        $betterReflectionMethod = $this->createMock(BetterReflectionMethod::class);
        $betterReflectionMethod
            ->method('getClosure')
            ->willThrowException(NoObjectProvided::create());

        $reflectionMethodAdapter = new ReflectionMethodAdapter($betterReflectionMethod);

        self::assertNull($reflectionMethodAdapter->getClosure());
    }

    public function testGetClosureReturnsNullWhenNotAnObject(): void
    {
        $betterReflectionMethod = $this->createMock(BetterReflectionMethod::class);
        $betterReflectionMethod
            ->method('getClosure')
            ->willThrowException(new TypeError());

        $reflectionMethodAdapter = new ReflectionMethodAdapter($betterReflectionMethod);

        self::assertNull($reflectionMethodAdapter->getClosure('string'));
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

    public function testInvokeReturnsNullWhenNotAnObject(): void
    {
        $betterReflectionMethod = $this->createMock(BetterReflectionMethod::class);
        $betterReflectionMethod
            ->method('isPublic')
            ->willReturn(true);
        $betterReflectionMethod
            ->method('invoke')
            ->willThrowException(new TypeError());

        $reflectionMethodAdapter = new ReflectionMethodAdapter($betterReflectionMethod);

        self::assertNull($reflectionMethodAdapter->invoke('string'));
    }

    public function testInvokeArgsReturnsNullWhenNotAnObject(): void
    {
        $betterReflectionMethod = $this->createMock(BetterReflectionMethod::class);
        $betterReflectionMethod
            ->method('isPublic')
            ->willReturn(true);
        $betterReflectionMethod
            ->method('invokeArgs')
            ->willThrowException(new TypeError());

        $reflectionMethodAdapter = new ReflectionMethodAdapter($betterReflectionMethod);

        self::assertNull($reflectionMethodAdapter->invokeArgs('string', []));
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

    public function testInvokeThrowsExceptionWhenPropertyNotAccessible(): void
    {
        $betterReflectionMethod = $this->createMock(BetterReflectionMethod::class);
        $betterReflectionMethod
            ->method('isPublic')
            ->willReturn(false);

        $reflectionMethodAdapter = new ReflectionMethodAdapter($betterReflectionMethod);

        $this->expectException(CoreReflectionException::class);
        $reflectionMethodAdapter->invoke();
    }

    public function testInvokeArgsThrowsExceptionWhenPropertyNotAccessible(): void
    {
        $betterReflectionMethod = $this->createMock(BetterReflectionMethod::class);
        $betterReflectionMethod
            ->method('isPublic')
            ->willReturn(false);

        $reflectionMethodAdapter = new ReflectionMethodAdapter($betterReflectionMethod);

        $this->expectException(CoreReflectionException::class);
        $reflectionMethodAdapter->invokeArgs();
    }
}
