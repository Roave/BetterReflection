<?php
declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection\Adapter;

use Exception;
use PHPUnit\Framework\TestCase;
use ReflectionClass as CoreReflectionClass;
use ReflectionException as CoreReflectionException;
use ReflectionMethod as CoreReflectionMethod;
use Roave\BetterReflection\Reflection\Adapter\Exception\NotImplemented;
use Roave\BetterReflection\Reflection\Adapter\ReflectionClass as ReflectionClassAdapter;
use Roave\BetterReflection\Reflection\Adapter\ReflectionMethod as ReflectionMethodAdapter;
use Roave\BetterReflection\Reflection\Exception\NoObjectProvided;
use Roave\BetterReflection\Reflection\Exception\NotAnObject;
use Roave\BetterReflection\Reflection\Exception\ObjectNotInstanceOfClass;
use Roave\BetterReflection\Reflection\ReflectionClass as BetterReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionMethod as BetterReflectionMethod;
use Roave\BetterReflection\Reflection\ReflectionParameter as BetterReflectionParameter;
use Roave\BetterReflection\Reflection\ReflectionType as BetterReflectionType;
use stdClass;

/**
 * @covers \Roave\BetterReflection\Reflection\Adapter\ReflectionMethod
 */
class ReflectionMethodTest extends TestCase
{
    public function coreReflectionMethodNamesProvider() : array
    {
        $methods = \get_class_methods(CoreReflectionMethod::class);
        return \array_combine($methods, \array_map(function (string $i) : array {
            return [$i];
        }, $methods));
    }

    /**
     * @param string $methodName
     * @dataProvider coreReflectionMethodNamesProvider
     */
    public function testCoreReflectionMethods(string $methodName) : void
    {
        $reflectionMethodAdapterReflection = new CoreReflectionClass(ReflectionMethodAdapter::class);
        self::assertTrue($reflectionMethodAdapterReflection->hasMethod($methodName));
    }

    public function methodExpectationProvider() : array
    {
        $mockParameter = $this->createMock(BetterReflectionParameter::class);

        $mockMethod = $this->createMock(BetterReflectionMethod::class);

        $mockType = $this->createMock(BetterReflectionType::class);

        $closure = function () : void {
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
            ['getExtensionName', NotImplemented::class, null, []],
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
            ['invoke', NotImplemented::class, null, [new stdClass(), '']],
            ['invokeArgs', NotImplemented::class, null, [new stdClass(), []]],
            ['getPrototype', null, $mockMethod, []],
            ['setAccessible', NotImplemented::class, null, [true]],
        ];
    }

    /**
     * @param string $methodName
     * @param string|null $expectedException
     * @param mixed $returnValue
     * @param array $args
     * @dataProvider methodExpectationProvider
     */
    public function testAdapterMethods(string $methodName, ?string $expectedException, $returnValue, array $args) : void
    {
        /** @var BetterReflectionMethod|\PHPUnit_Framework_MockObject_MockObject $reflectionStub */
        $reflectionStub = $this->createMock(BetterReflectionMethod::class);

        if (null === $expectedException) {
            $reflectionStub->expects($this->once())
                ->method($methodName)
                ->with(...$args)
                ->will($this->returnValue($returnValue));
        }

        if (null !== $expectedException) {
            $this->expectException($expectedException);
        }

        $adapter = new ReflectionMethodAdapter($reflectionStub);
        $adapter->{$methodName}(...$args);
    }

    public function testExport() : void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Unable to export statically');
        ReflectionMethodAdapter::export('\stdClass', 'foo');
    }

    public function testGetFileNameReturnsFalseWhenNoFileName() : void
    {
        $betterReflectionMethod = $this->createMock(BetterReflectionMethod::class);
        $betterReflectionMethod
            ->method('getFileName')
            ->willReturn(null);

        $betterReflectionMethod = new ReflectionMethodAdapter($betterReflectionMethod);

        self::assertFalse($betterReflectionMethod->getFileName());
    }

    public function testGetDocCommentReturnsFalseWhenNoDocComment() : void
    {
        $betterReflectionMethod = $this->createMock(BetterReflectionMethod::class);
        $betterReflectionMethod
            ->method('getDocComment')
            ->willReturn('');

        $reflectionMethodAdapter = new ReflectionMethodAdapter($betterReflectionMethod);

        self::assertFalse($reflectionMethodAdapter->getDocComment());
    }

    public function testGetDeclaringClass() : void
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

    public function testGetClosureReturnsNullWhenNoObject() : void
    {
        $betterReflectionMethod = $this->createMock(BetterReflectionMethod::class);
        $betterReflectionMethod
            ->method('getClosure')
            ->willThrowException(NoObjectProvided::create());

        $reflectionMethodAdapter = new ReflectionMethodAdapter($betterReflectionMethod);

        $this->assertNull($reflectionMethodAdapter->getClosure());
    }

    public function testGetClosureReturnsNullWhenNotAnObject() : void
    {
        $betterReflectionMethod = $this->createMock(BetterReflectionMethod::class);
        $betterReflectionMethod
            ->method('getClosure')
            ->willThrowException(NotAnObject::fromNonObject('string'));

        $reflectionMethodAdapter = new ReflectionMethodAdapter($betterReflectionMethod);

        $this->assertNull($reflectionMethodAdapter->getClosure('string'));
    }

    public function testGetClosureThrowsExceptionWhenObjectNotInstanceOfClass() : void
    {
        $betterReflectionMethod = $this->createMock(BetterReflectionMethod::class);
        $betterReflectionMethod
            ->method('getClosure')
            ->willThrowException(ObjectNotInstanceOfClass::fromClassName('Foo'));

        $reflectionMethodAdapter = new ReflectionMethodAdapter($betterReflectionMethod);

        $this->expectException(CoreReflectionException::class);
        $reflectionMethodAdapter->getClosure(new stdClass());
    }
}
