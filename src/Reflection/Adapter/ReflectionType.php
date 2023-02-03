<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection\Adapter;

use ReflectionType as CoreReflectionType;
use Roave\BetterReflection\Reflection\ReflectionIntersectionType as BetterReflectionIntersectionType;
use Roave\BetterReflection\Reflection\ReflectionNamedType as BetterReflectionNamedType;
use Roave\BetterReflection\Reflection\ReflectionType as BetterReflectionType;
use Roave\BetterReflection\Reflection\ReflectionUnionType as BetterReflectionUnionType;

use function array_filter;
use function array_values;
use function count;

/** @psalm-immutable */
abstract class ReflectionType extends CoreReflectionType
{
    /** @psalm-pure */
    public static function fromTypeOrNull(BetterReflectionUnionType|BetterReflectionNamedType|BetterReflectionIntersectionType|null $betterReflectionType): ReflectionUnionType|ReflectionNamedType|ReflectionIntersectionType|null
    {
        return $betterReflectionType !== null ? self::fromType($betterReflectionType) : null;
    }

    /**
     * @internal
     *
     * @psalm-pure
     */
    public static function fromType(BetterReflectionNamedType|BetterReflectionUnionType|BetterReflectionIntersectionType $betterReflectionType): ReflectionUnionType|ReflectionNamedType|ReflectionIntersectionType
    {
        if ($betterReflectionType instanceof BetterReflectionUnionType) {
            // php-src has this weird behavior where a union type composed of a single type `T`
            // together with `null` means that a `ReflectionNamedType` for `?T` is produced,
            // rather than `T|null`. This is done to keep BC compatibility with PHP 7.1 (which
            // introduced nullable types), but at reflection level, this is mostly a nuisance.
            // In order to keep parity with core, we stashed this weird behavior in here.
            $nonNullTypes = array_values(array_filter(
                $betterReflectionType->getTypes(),
                static fn (BetterReflectionType $type): bool => ! ($type instanceof BetterReflectionNamedType && $type->getName() === 'null'),
            ));

            if (
                $betterReflectionType->allowsNull()
                && count($nonNullTypes) === 1
                && $nonNullTypes[0] instanceof BetterReflectionNamedType
            ) {
                return new ReflectionNamedType($nonNullTypes[0], true);
            }

            return new ReflectionUnionType($betterReflectionType);
        }

        if ($betterReflectionType instanceof BetterReflectionIntersectionType) {
            return new ReflectionIntersectionType($betterReflectionType);
        }

        return new ReflectionNamedType($betterReflectionType, $betterReflectionType->allowsNull());
    }
}
