<?php
declare(strict_types=1);

namespace Rector\BetterReflection\Reflector\Exception;

use Rector\BetterReflection\Identifier\Identifier;
use RuntimeException;

class IdentifierNotFound extends RuntimeException
{
    /**
     * @var Identifier
     */
    private $identifier;

    public function __construct(string $message, Identifier $identifier)
    {
        parent::__construct($message);

        $this->identifier = $identifier;
    }

    /**
     * @return Identifier
     */
    public function getIdentifier() : Identifier
    {
        return $this->identifier;
    }

    public static function fromIdentifier(Identifier $identifier) : self
    {
        return new self(\sprintf(
            '%s "%s" could not be found in the located source',
            $identifier->getType()->getName(),
            $identifier->getName()
        ), $identifier);
    }
}
