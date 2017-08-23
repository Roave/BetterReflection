<?php
declare(strict_types=1);

namespace Roave\BetterReflectionTest\Identifier;

use Roave\BetterReflection\Identifier\IdentifierType;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionFunction;
use InvalidArgumentException;

/**
 * @covers \Roave\BetterReflection\Identifier\IdentifierType
 */
class IdentifierTypeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @return string[][]
     */
    public function possibleIdentifierTypesProvider() : array
    {
        return [
            [IdentifierType::IDENTIFIER_CLASS],
        ];
    }

    /**
     * @param string $full
     * @dataProvider possibleIdentifierTypesProvider
     */
    public function testPossibleIdentifierTypes(string $full) : void
    {
        $type = new IdentifierType($full);
        self::assertSame($full, $type->getName());
    }

    public function testThrowsAnExceptionWhenInvalidTypeGiven() : void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('foo is not a valid identifier type');
        new IdentifierType('foo');
    }

    public function testIsMatchingReflectorClass() : void
    {
        $reflectionClass = $this->createMock(ReflectionClass::class);

        $type = new IdentifierType(IdentifierType::IDENTIFIER_CLASS);

        self::assertTrue($type->isMatchingReflector($reflectionClass));
    }

    public function testIsMatchingReflectorFunction() : void
    {
        $reflectionFunction = $this->createMock(ReflectionFunction::class);

        $type = new IdentifierType(IdentifierType::IDENTIFIER_FUNCTION);

        self::assertTrue($type->isMatchingReflector($reflectionFunction));
    }

    public function testIsMatchingReflectorReturnsFalseWhenTypeIsInvalid() : void
    {
        $classType = new IdentifierType(IdentifierType::IDENTIFIER_CLASS);

        // We must use reflection to hack the value, because we cannot create
        // an IdentifierType with an invalid type
        $reflection = new \ReflectionObject($classType);
        $prop       = $reflection->getProperty('name');
        $prop->setAccessible(true);
        $prop->setValue($classType, 'nonsense');

        $reflectionClass = $this->createMock(ReflectionClass::class);

        self::assertFalse($classType->isMatchingReflector($reflectionClass));
    }

    public function testIsTypesForClass() : void
    {
        $classType = new IdentifierType(IdentifierType::IDENTIFIER_CLASS);

        self::assertTrue($classType->isClass());
        self::assertFalse($classType->isFunction());
    }

    public function testIsTypesForFunction() : void
    {
        $functionType = new IdentifierType(IdentifierType::IDENTIFIER_FUNCTION);

        self::assertFalse($functionType->isClass());
        self::assertTrue($functionType->isFunction());
    }
}
