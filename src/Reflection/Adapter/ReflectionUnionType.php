<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection\Adapter;

use ReflectionUnionType as CoreReflectionUnionType;
use Roave\BetterReflection\Reflection\ReflectionType as BetterReflectionType;
use Roave\BetterReflection\Reflection\ReflectionUnionType as BetterReflectionUnionType;

use function array_map;
use function assert;

final class ReflectionUnionType extends CoreReflectionUnionType
{
    public function __construct(private BetterReflectionUnionType $betterReflectionType)
    {
    }

    /** @return non-empty-list<ReflectionNamedType|ReflectionIntersectionType> */
    public function getTypes(): array
    {
        return array_map(static function (BetterReflectionType $type): ReflectionNamedType|ReflectionIntersectionType {
            $adapterType = ReflectionType::fromType($type);
            assert($adapterType instanceof ReflectionNamedType || $adapterType instanceof ReflectionIntersectionType);

            return $adapterType;
        }, $this->betterReflectionType->getTypes());
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
