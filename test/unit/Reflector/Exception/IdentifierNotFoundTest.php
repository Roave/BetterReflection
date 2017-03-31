<?php

namespace Roave\BetterReflectionTest\Reflector\Exception;

use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\Identifier\IdentifierType;
use Roave\BetterReflection\Reflector\Exception\IdentifierNotFound;

/**
 * @covers \Roave\BetterReflection\Reflector\Exception\IdentifierNotFound
 */
class IdentifierNotFoundTest extends \PHPUnit_Framework_TestCase
{
    public function testFromNonObject()
    {
        $identifier = new Identifier('myIdentifier', new IdentifierType(IdentifierType::IDENTIFIER_CLASS));

        $exception = IdentifierNotFound::fromIdentifier($identifier);

        self::assertInstanceOf(IdentifierNotFound::class, $exception);
        self::assertSame(IdentifierType::IDENTIFIER_CLASS . ' "myIdentifier" could not be found in the located source', $exception->getMessage());
        self::assertSame($identifier, $exception->getIdentifier());
    }
}
