<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection\Adapter;

use ReflectionType as CoreReflectionType;
use Roave\BetterReflection\Reflection\ReflectionNamedType as BetterReflectionNamedType;
use Roave\BetterReflection\Reflection\ReflectionUnionType as BetterReflectionUnionType;

abstract class ReflectionType extends CoreReflectionType
{
    public static function fromTypeOrNull(BetterReflectionNamedType|BetterReflectionUnionType|null $betterReflectionType): ReflectionUnionType|ReflectionNamedType|null
    {
        if ($betterReflectionType === null) {
            return null;
        }

        if ($betterReflectionType instanceof BetterReflectionUnionType) {
            return new ReflectionUnionType($betterReflectionType);
        }

        return new ReflectionNamedType($betterReflectionType);
    }
}
