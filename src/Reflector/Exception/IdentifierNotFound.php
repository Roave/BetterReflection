<?php

namespace BetterReflection\Reflector\Exception;

use BetterReflection\Identifier\Identifier;

class IdentifierNotFound extends \RuntimeException
{
    public static function fromIdentifier(Identifier $identifier)
    {
        return new self(sprintf(
            '%s "%s" could not be found in the located source',
            $identifier->getType()->getName(),
            $identifier->getName()
        ));
    }
}
