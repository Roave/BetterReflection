<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection\Adapter;

use ReflectionUnionType as CoreReflectionUnionType;
use Roave\BetterReflection\Reflection\ReflectionNamedType as BetterReflectionNamedType;
use Roave\BetterReflection\Reflection\ReflectionUnionType as BetterReflectionUnionType;

use function array_map;

class ReflectionUnionType extends CoreReflectionUnionType
{
    public function __construct(private BetterReflectionUnionType $betterReflectionType)
    {
    }

    /**
     * @return array<ReflectionNamedType|ReflectionUnionType|null>
     */
    public function getTypes(): array
    {
        return array_map(static fn (BetterReflectionNamedType|BetterReflectionUnionType $type): ReflectionNamedType|ReflectionUnionType|null => ReflectionType::fromTypeOrNull($type), $this->betterReflectionType->getTypes());
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
