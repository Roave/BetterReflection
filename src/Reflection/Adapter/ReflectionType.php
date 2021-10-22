<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection\Adapter;

use ReflectionType as CoreReflectionType;
use Roave\BetterReflection\Reflection\ReflectionIntersectionType as BetterReflectionIntersectionType;
use Roave\BetterReflection\Reflection\ReflectionNamedType as BetterReflectionNamedType;
use Roave\BetterReflection\Reflection\ReflectionUnionType as BetterReflectionUnionType;

abstract class ReflectionType extends CoreReflectionType
{
    public static function fromTypeOrNull(BetterReflectionNamedType|BetterReflectionUnionType|BetterReflectionIntersectionType|null $betterReflectionType): ReflectionUnionType|ReflectionNamedType|ReflectionIntersectionType|null
    {
        if ($betterReflectionType === null) {
            return null;
        }

        if ($betterReflectionType instanceof BetterReflectionUnionType) {
            return new ReflectionUnionType($betterReflectionType);
        }

        if ($betterReflectionType instanceof BetterReflectionIntersectionType) {
            return new ReflectionIntersectionType($betterReflectionType);
        }

        return new ReflectionNamedType($betterReflectionType);
    }
}
