<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection\Adapter;

use ReflectionIntersectionType as CoreReflectionIntersectionType;
use Roave\BetterReflection\Reflection\ReflectionIntersectionType as BetterReflectionIntersectionType;
use Roave\BetterReflection\Reflection\ReflectionNamedType as BetterReflectionNamedType;

use function array_map;
use function assert;

/** @psalm-immutable */
class ReflectionIntersectionType extends CoreReflectionIntersectionType
{
    public function __construct(private BetterReflectionIntersectionType $betterReflectionType)
    {
    }

    /** @return non-empty-list<ReflectionNamedType> */
    public function getTypes(): array
    {
        return array_map(static function (BetterReflectionNamedType $type): ReflectionNamedType {
            $adapterType = ReflectionType::fromType($type);
            assert($adapterType instanceof ReflectionNamedType);

            return $adapterType;
        }, $this->betterReflectionType->getTypes());
    }

    /** @return non-empty-string */
    public function __toString(): string
    {
        return $this->betterReflectionType->__toString();
    }

    /** @return false */
    public function allowsNull(): bool
    {
        return $this->betterReflectionType->allowsNull();
    }
}
