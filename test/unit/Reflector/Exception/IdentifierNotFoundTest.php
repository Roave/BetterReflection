<?php
declare(strict_types=1);

namespace Rector\BetterReflectionTest\Reflector\Exception;

use PHPUnit\Framework\TestCase;
use Rector\BetterReflection\Identifier\Identifier;
use Rector\BetterReflection\Identifier\IdentifierType;
use Rector\BetterReflection\Reflector\Exception\IdentifierNotFound;

/**
 * @covers \Rector\BetterReflection\Reflector\Exception\IdentifierNotFound
 */
class IdentifierNotFoundTest extends TestCase
{
    public function testFromNonObject() : void
    {
        $identifier = new Identifier('myIdentifier', new IdentifierType(IdentifierType::IDENTIFIER_CLASS));

        $exception = IdentifierNotFound::fromIdentifier($identifier);

        self::assertInstanceOf(IdentifierNotFound::class, $exception);
        self::assertSame(IdentifierType::IDENTIFIER_CLASS . ' "myIdentifier" could not be found in the located source', $exception->getMessage());
        self::assertSame($identifier, $exception->getIdentifier());
    }
}
