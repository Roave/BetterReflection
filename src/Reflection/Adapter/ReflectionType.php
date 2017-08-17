<?php

namespace Roave\BetterReflection\Reflection\Adapter;

use ReflectionType as CoreReflectionType;
use Roave\BetterReflection\Reflection\ReflectionType as BetterReflectionType;

class ReflectionType extends CoreReflectionType
{
    /**
     * @var BetterReflectionType
     */
    private $betterReflectionType;

    public function __construct(BetterReflectionType $betterReflectionType)
    {
        $this->betterReflectionType = $betterReflectionType;
    }

    public static function fromReturnTypeOrNull(?BetterReflectionType $betterReflectionType) : ?self
    {
        if (null === $betterReflectionType) {
            return null;
        }

        return new self($betterReflectionType);
    }

    /**
     * {@inheritDoc}
     */
    public function __toString()
    {
        return $this->betterReflectionType->__toString();
    }

    /**
     * {@inheritDoc}
     */
    public function allowsNull()
    {
        return $this->betterReflectionType->allowsNull();
    }

    /**
     * {@inheritDoc}
     */
    public function isBuiltin()
    {
        return $this->betterReflectionType->isBuiltin();
    }
}
