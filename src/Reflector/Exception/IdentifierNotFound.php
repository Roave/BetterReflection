<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflector\Exception;

use Roave\BetterReflection\Identifier\Identifier;
use RuntimeException;

use function sprintf;

class IdentifierNotFound extends RuntimeException
{
    public function __construct(string $message, private Identifier $identifier)
    {
        parent::__construct($message);
    }

    public function getIdentifier(): Identifier
    {
        return $this->identifier;
    }

    public static function fromIdentifier(Identifier $identifier): self
    {
        return new self(sprintf(
            '%s "%s" could not be found in the located source',
            $identifier->getType()->getName(),
            $identifier->getName(),
        ), $identifier);
    }
}
