<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection\Adapter;

use ReflectionNamedType as CoreReflectionNamedType;
use Roave\BetterReflection\Reflection\ReflectionType as BetterReflectionType;

class ReflectionNamedType extends CoreReflectionNamedType
{
    /** @var BetterReflectionType */
    private $betterReflectionType;

    public function __construct(BetterReflectionType $betterReflectionType)
    {
        $this->betterReflectionType = $betterReflectionType;
    }

    public static function fromReturnTypeOrNull(?BetterReflectionType $betterReflectionType) : ?self
    {
        if ($betterReflectionType === null) {
            return null;
        }

        return new self($betterReflectionType);
    }

    public function getName() : string
    {
        return $this->betterReflectionType->getName();
    }

    public function __toString() : string
    {
        return $this->betterReflectionType->__toString();
    }

    public function allowsNull() : bool
    {
        return $this->betterReflectionType->allowsNull();
    }

    public function isBuiltin() : bool
    {
        $type = (string) $this->betterReflectionType;

        if ($type === 'self' || $type === 'parent') {
            return false;
        }

        return $this->betterReflectionType->isBuiltin();
    }
}
