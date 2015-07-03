<?php

namespace BetterReflectionTest\Identifier;

use BetterReflection\Identifier\Identifier;
use BetterReflection\Identifier\IdentifierType;

/**
 * @covers \BetterReflection\Identifier\Identifier
 */
class IdentifierTest extends \PHPUnit_Framework_TestCase
{
    public function testValue()
    {
        $beforeName = '\Some\Thing\Here';
        $afterName = 'Some\Thing\Here';

        $identifierType = new IdentifierType(IdentifierType::IDENTIFIER_CLASS);
        $identifier = new Identifier($beforeName, $identifierType);

        $this->assertSame($afterName, $identifier->getName());
        $this->assertSame($identifierType, $identifier->getType());
    }
}
