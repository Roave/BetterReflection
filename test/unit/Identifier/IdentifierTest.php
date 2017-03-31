<?php

namespace Roave\BetterReflectionTest\Identifier;

use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\Identifier\IdentifierType;

/**
 * @covers \Roave\BetterReflection\Identifier\Identifier
 */
class IdentifierTest extends \PHPUnit_Framework_TestCase
{
    public function testGetName()
    {
        $beforeName = '\Some\Thing\Here';
        $afterName = 'Some\Thing\Here';

        $identifier = new Identifier($beforeName, new IdentifierType(IdentifierType::IDENTIFIER_CLASS));
        self::assertSame($afterName, $identifier->getName());
    }

    public function testGetType()
    {
        $identifierType = new IdentifierType(IdentifierType::IDENTIFIER_CLASS);

        $identifier = new Identifier('Foo', $identifierType);
        self::assertSame($identifierType, $identifier->getType());
    }

    public function testIsTypesForClass()
    {
        $identifier = new Identifier('Foo', new IdentifierType(IdentifierType::IDENTIFIER_CLASS));

        self::assertTrue($identifier->isClass());
        self::assertFalse($identifier->isFunction());
    }

    public function testIsTypesForFunction()
    {
        $identifier = new Identifier('Foo', new IdentifierType(IdentifierType::IDENTIFIER_FUNCTION));

        self::assertFalse($identifier->isClass());
        self::assertTrue($identifier->isFunction());
    }
}
