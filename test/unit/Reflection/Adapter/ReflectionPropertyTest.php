<?php
declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection\Adapter;

use Exception;
use PHPUnit\Framework\TestCase;
use ReflectionClass as CoreReflectionClass;
use ReflectionException as CoreReflectionException;
use ReflectionProperty as CoreReflectionProperty;
use Roave\BetterReflection\Reflection\Adapter\ReflectionClass as ReflectionClassAdapter;
use Roave\BetterReflection\Reflection\Adapter\ReflectionProperty as ReflectionPropertyAdapter;
use Roave\BetterReflection\Reflection\Exception\NoObjectProvided;
use Roave\BetterReflection\Reflection\Exception\NotAnObject;
use Roave\BetterReflection\Reflection\Exception\ObjectNotInstanceOfClass;
use Roave\BetterReflection\Reflection\ReflectionClass as BetterReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionProperty as BetterReflectionProperty;
use stdClass;

/**
 * @covers \Roave\BetterReflection\Reflection\Adapter\ReflectionProperty
 */
class ReflectionPropertyTest extends TestCase
{
    public function coreReflectionPropertyNamesProvider() : array
    {
        $methods = \get_class_methods(CoreReflectionProperty::class);
        return \array_combine($methods, \array_map(function (string $i) : array {
            return [$i];
        }, $methods));
    }

    /**
     * @param string $methodName
     * @dataProvider coreReflectionPropertyNamesProvider
     */
    public function testCoreReflectionPropertys(string $methodName) : void
    {
        $reflectionPropertyAdapterReflection = new CoreReflectionClass(ReflectionPropertyAdapter::class);
        self::assertTrue($reflectionPropertyAdapterReflection->hasMethod($methodName));
    }

    public function methodExpectationProvider() : array
    {
        return [
            ['__toString', '', []],
            ['getName', '', []],
            ['isPublic', true, []],
            ['isPrivate', true, []],
            ['isProtected', true, []],
            ['isStatic', true, []],
            ['isDefault', true, []],
            ['getModifiers', 123, []],
            ['getDocComment', '', []],
        ];
    }

    /**
     * @param string $methodName
     * @param mixed $returnValue
     * @param array $args
     * @dataProvider methodExpectationProvider
     */
    public function testAdapterMethods(string $methodName, $returnValue, array $args) : void
    {
        /** @var BetterReflectionProperty|\PHPUnit_Framework_MockObject_MockObject $reflectionStub */
        $reflectionStub = $this->createMock(BetterReflectionProperty::class);

        $reflectionStub->expects($this->once())
            ->method($methodName)
            ->with(...$args)
            ->will($this->returnValue($returnValue));

        $adapter = new ReflectionPropertyAdapter($reflectionStub);
        $adapter->{$methodName}(...$args);
    }

    public function testExport() : void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Unable to export statically');
        ReflectionPropertyAdapter::export('foo', 0);
    }

    public function testGetDocCommentReturnsFalseWhenNoDocComment() : void
    {
        $betterReflectionProperty = $this->createMock(BetterReflectionProperty::class);
        $betterReflectionProperty
            ->method('getDocComment')
            ->willReturn('');

        $reflectionPropertyAdapter = new ReflectionPropertyAdapter($betterReflectionProperty);

        self::assertFalse($reflectionPropertyAdapter->getDocComment());
    }

    public function testGetDeclaringClass() : void
    {
        $betterReflectionClass = $this->createMock(BetterReflectionClass::class);
        $betterReflectionClass
            ->method('getName')
            ->willReturn('DeclaringClass');

        $betterReflectionProperty = $this->createMock(BetterReflectionProperty::class);
        $betterReflectionProperty
            ->method('getImplementingClass')
            ->willReturn($betterReflectionClass);

        $reflectionPropertyAdapter = new ReflectionPropertyAdapter($betterReflectionProperty);

        self::assertInstanceOf(ReflectionClassAdapter::class, $reflectionPropertyAdapter->getDeclaringClass());
        self::assertSame('DeclaringClass', $reflectionPropertyAdapter->getDeclaringClass()->getName());
    }

    public function testGetValueReturnsNullWhenNoObject() : void
    {
        $betterReflectionProperty = $this->createMock(BetterReflectionProperty::class);
        $betterReflectionProperty
            ->method('isPublic')
            ->willReturn(true);
        $betterReflectionProperty
            ->method('getValue')
            ->willThrowException(NoObjectProvided::create());

        $reflectionPropertyAdapter = new ReflectionPropertyAdapter($betterReflectionProperty);

        $this->assertNull($reflectionPropertyAdapter->getValue());
    }

    public function testSetValueReturnsNullWhenNoObject() : void
    {
        $betterReflectionProperty = $this->createMock(BetterReflectionProperty::class);
        $betterReflectionProperty
            ->method('isPublic')
            ->willReturn(true);
        $betterReflectionProperty
            ->method('setValue')
            ->willThrowException(NoObjectProvided::create());

        $reflectionPropertyAdapter = new ReflectionPropertyAdapter($betterReflectionProperty);

        $this->assertNull($reflectionPropertyAdapter->setValue(null));
    }

    public function testGetValueReturnsNullWhenNotAnObject() : void
    {
        $betterReflectionProperty = $this->createMock(BetterReflectionProperty::class);
        $betterReflectionProperty
            ->method('isPublic')
            ->willReturn(true);
        $betterReflectionProperty
            ->method('getValue')
            ->willThrowException(NotAnObject::fromNonObject('string'));

        $reflectionPropertyAdapter = new ReflectionPropertyAdapter($betterReflectionProperty);

        $this->assertNull($reflectionPropertyAdapter->getValue('string'));
    }

    public function testSetValueReturnsNullWhenNotAnObject() : void
    {
        $betterReflectionProperty = $this->createMock(BetterReflectionProperty::class);
        $betterReflectionProperty
            ->method('isPublic')
            ->willReturn(true);
        $betterReflectionProperty
            ->method('setValue')
            ->willThrowException(NotAnObject::fromNonObject('string'));

        $reflectionPropertyAdapter = new ReflectionPropertyAdapter($betterReflectionProperty);

        $this->assertNull($reflectionPropertyAdapter->setValue('string'));
    }

    public function testGetValueThrowsExceptionWhenPropertyNotAccessible() : void
    {
        $betterReflectionProperty = $this->createMock(BetterReflectionProperty::class);
        $betterReflectionProperty
            ->method('isPublic')
            ->willReturn(false);

        $reflectionPropertyAdapter = new ReflectionPropertyAdapter($betterReflectionProperty);

        $this->expectException(CoreReflectionException::class);
        $reflectionPropertyAdapter->getValue(new stdClass());
    }

    public function testSetValueThrowsExceptionWhenPropertyNotAccessible() : void
    {
        $betterReflectionProperty = $this->createMock(BetterReflectionProperty::class);
        $betterReflectionProperty
            ->method('isPublic')
            ->willReturn(false);

        $reflectionPropertyAdapter = new ReflectionPropertyAdapter($betterReflectionProperty);

        $this->expectException(CoreReflectionException::class);
        $reflectionPropertyAdapter->setValue(new stdClass());
    }

    public function testSetAccessibleAndGetValue() : void
    {
        $betterReflectionProperty = $this->createMock(BetterReflectionProperty::class);
        $betterReflectionProperty
            ->method('isPublic')
            ->willReturn(false);
        $betterReflectionProperty
            ->method('getValue')
            ->willReturn(123);

        $reflectionPropertyAdapter = new ReflectionPropertyAdapter($betterReflectionProperty);

        $reflectionPropertyAdapter->setAccessible(true);
        self::assertSame(123, $reflectionPropertyAdapter->getValue(new stdClass()));
    }

    public function testSetAccessibleAndSetValue() : void
    {
        $betterReflectionProperty = $this->createMock(BetterReflectionProperty::class);
        $betterReflectionProperty
            ->method('isPublic')
            ->willReturn(false);
        $betterReflectionProperty
            ->expects($this->once())
            ->method('setValue')
            ->with(null, 123);

        $reflectionPropertyAdapter = new ReflectionPropertyAdapter($betterReflectionProperty);

        $reflectionPropertyAdapter->setAccessible(true);
        $reflectionPropertyAdapter->setValue(null, 123);
    }

    public function testGetValueThrowsExceptionWhenObjectNotInstanceOfClass() : void
    {
        $betterReflectionProperty = $this->createMock(BetterReflectionProperty::class);
        $betterReflectionProperty
            ->method('isPublic')
            ->willReturn(true);
        $betterReflectionProperty
            ->method('getValue')
            ->willThrowException(ObjectNotInstanceOfClass::fromClassName('Foo'));

        $reflectionPropertyAdapter = new ReflectionPropertyAdapter($betterReflectionProperty);

        $this->expectException(CoreReflectionException::class);
        $reflectionPropertyAdapter->getValue(new stdClass());
    }

    public function testSetValueThrowsExceptionWhenObjectNotInstanceOfClass() : void
    {
        $betterReflectionProperty = $this->createMock(BetterReflectionProperty::class);
        $betterReflectionProperty
            ->method('isPublic')
            ->willReturn(true);
        $betterReflectionProperty
            ->method('setValue')
            ->willThrowException(ObjectNotInstanceOfClass::fromClassName('Foo'));

        $reflectionPropertyAdapter = new ReflectionPropertyAdapter($betterReflectionProperty);

        $this->expectException(CoreReflectionException::class);
        $reflectionPropertyAdapter->setValue(new stdClass());
    }
}
