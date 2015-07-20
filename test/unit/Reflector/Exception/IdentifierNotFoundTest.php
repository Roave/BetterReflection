<?php

namespace BetterReflectionTest\Reflector\Exception;

use BetterReflection\Identifier\Identifier;
use BetterReflection\Identifier\IdentifierType;
use BetterReflection\Reflector\Exception\IdentifierNotFound;

/**
 * @covers \BetterReflection\Reflector\Exception\IdentifierNotFound
 */
class IdentifierNotFoundTest extends \PHPUnit_Framework_TestCase
{
    public function testFromNonObject()
    {
        $exception = IdentifierNotFound::fromIdentifier(new Identifier(
            "myIdentifier",
            new IdentifierType(IdentifierType::IDENTIFIER_CLASS)
        ));

        $this->assertInstanceOf(IdentifierNotFound::class, $exception);
        $this->assertSame(IdentifierType::IDENTIFIER_CLASS . ' "myIdentifier" could not be found in the located source', $exception->getMessage());
    }
}
