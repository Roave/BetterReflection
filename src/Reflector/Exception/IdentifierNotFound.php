<?php

namespace BetterReflection\Reflector\Exception;

use BetterReflection\Identifier\Identifier;

class IdentifierNotFound extends \RuntimeException
{
    /**
     * @var Identifier
     */
    private $identifier;

    public function __construct($message, Identifier $identifier)
    {
        parent::__construct($message);

        $this->identifier = $identifier;
    }

    /**
     * @return Identifier
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    public static function fromIdentifier(Identifier $identifier)
    {
        return new self(sprintf(
            '%s "%s" could not be found in the located source',
            $identifier->getType()->getName(),
            $identifier->getName()
        ), $identifier);
    }
}
