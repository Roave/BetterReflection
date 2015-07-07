<?php

namespace BetterReflectionTest\Identifier;

use BetterReflection\Identifier\IdentifierType;
use BetterReflection\Reflection\ReflectionClass;
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

    public function testIsMatchingReflector()
    {
        $reflectionClass = $this->getMockBuilder(ReflectionClass::class)
            ->disableOriginalConstructor()
            ->getMock();

        $classType = new IdentifierType(IdentifierType::IDENTIFIER_CLASS);

        $this->assertTrue($classType->isMatchingReflector($reflectionClass));
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
}
