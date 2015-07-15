<?php

namespace BetterReflectionTest\Identifier;

use BetterReflection\Identifier\IdentifierType;
use BetterReflection\Reflection\ReflectionClass;
use BetterReflection\Reflection\ReflectionFunction;
use InvalidArgumentException;

/**
 * @covers \BetterReflection\Identifier\IdentifierType
 */
class IdentifierTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return string[][]
     */
    public function possibleIdentifierTypesProvider()
    {
        return [
            [IdentifierType::IDENTIFIER_CLASS],
        ];
    }

    /**
     * @param string $full
     * @dataProvider possibleIdentifierTypesProvider
     */
    public function testPossibleIdentifierTypes($full)
    {
        $type = new IdentifierType($full);
        $this->assertSame($full, $type->getName());
    }

    public function testThrowsAnExceptionWhenInvalidTypeGiven()
    {
        $this->setExpectedException(
            InvalidArgumentException::class,
            'foo is not a valid identifier type'
        );
        new IdentifierType('foo');
    }

    public function testIsMatchingReflectorClass()
    {
        $reflectionClass = $this->getMockBuilder(ReflectionClass::class)
            ->disableOriginalConstructor()
            ->getMock();

        $type = new IdentifierType(IdentifierType::IDENTIFIER_CLASS);

        $this->assertTrue($type->isMatchingReflector($reflectionClass));
    }

    public function testIsMatchingReflectorFunction()
    {
        $reflectionFunction = $this->getMockBuilder(ReflectionFunction::class)
            ->disableOriginalConstructor()
            ->getMock();

        $type = new IdentifierType(IdentifierType::IDENTIFIER_FUNCTION);

        $this->assertTrue($type->isMatchingReflector($reflectionFunction));
    }

    public function testIsMatchingReflectorReturnsFalseWhenTypeIsInvalid()
    {
        $classType = new IdentifierType(IdentifierType::IDENTIFIER_CLASS);

        // We must use reflection to hack the value, because we cannot create
        // an IdentifierType with an invalid type
        $reflection = new \ReflectionObject($classType);
        $prop = $reflection->getProperty('name');
        $prop->setAccessible(true);
        $prop->setValue($classType, 'nonsense');

        $reflectionClass = $this->getMockBuilder(ReflectionClass::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->assertFalse($classType->isMatchingReflector($reflectionClass));
    }

    public function testIsTypesForClass()
    {
        $classType = new IdentifierType(IdentifierType::IDENTIFIER_CLASS);

        $this->assertTrue($classType->isClass());
        $this->assertFalse($classType->isFunction());
    }

    public function testIsTypesForFunction()
    {
        $functionType = new IdentifierType(IdentifierType::IDENTIFIER_FUNCTION);

        $this->assertFalse($functionType->isClass());
        $this->assertTrue($functionType->isFunction());
    }
}
