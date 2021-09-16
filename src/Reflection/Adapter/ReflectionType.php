<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection\Adapter;

use ReflectionType as CoreReflectionType;
use Roave\BetterReflection\Reflection\ReflectionNamedType as BetterReflectionNamedType;
use Roave\BetterReflection\Reflection\ReflectionType as BetterReflectionType;
use Roave\BetterReflection\Reflection\ReflectionUnionType as BetterReflectionUnionType;

class ReflectionType extends CoreReflectionType
{
    private BetterReflectionType $betterReflectionType;

    public function __construct(BetterReflectionType $betterReflectionType)
    {
        $this->betterReflectionType = $betterReflectionType;
    }

    public static function fromTypeOrNull(?BetterReflectionType $betterReflectionType): ReflectionUnionType|ReflectionNamedType|self|null
    {
        if ($betterReflectionType === null) {
            return null;
        }

        if ($betterReflectionType instanceof BetterReflectionUnionType) {
            return new ReflectionUnionType($betterReflectionType);
        }

        if ($betterReflectionType instanceof BetterReflectionNamedType) {
            return new ReflectionNamedType($betterReflectionType);
        }

        return new self($betterReflectionType);
    }

    public function __toString(): string
    {
        return $this->betterReflectionType->__toString();
    }

    public function allowsNull(): bool
    {
        return $this->betterReflectionType->allowsNull();
    }
}
