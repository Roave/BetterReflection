<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection\Adapter;

use ReflectionUnionType as CoreReflectionUnionType;
use Roave\BetterReflection\Reflection\ReflectionIntersectionType as BetterReflectionIntersectionType;
use Roave\BetterReflection\Reflection\ReflectionNamedType as BetterReflectionNamedType;
use Roave\BetterReflection\Reflection\ReflectionUnionType as BetterReflectionUnionType;

use function array_filter;
use function array_map;

final class ReflectionUnionType extends CoreReflectionUnionType
{
    public function __construct(private BetterReflectionUnionType $betterReflectionType)
    {
    }

    /** @return array<ReflectionNamedType> */
    public function getTypes(): array
    {
        return array_filter(
            array_map(static fn (BetterReflectionNamedType|BetterReflectionUnionType|BetterReflectionIntersectionType $type): ReflectionNamedType|ReflectionUnionType|ReflectionIntersectionType|null => ReflectionType::fromTypeOrNull($type), $this->betterReflectionType->getTypes()),
            static fn (ReflectionNamedType|ReflectionUnionType|ReflectionIntersectionType|null $type): bool => $type instanceof ReflectionNamedType,
        );
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
