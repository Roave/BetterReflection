<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection\Adapter;

use ReflectionNamedType as CoreReflectionNamedType;
use Roave\BetterReflection\Reflection\ReflectionNamedType as BetterReflectionNamedType;

use function strtolower;

/** @psalm-suppress MissingImmutableAnnotation */
final class ReflectionNamedType extends CoreReflectionNamedType
{
    public function __construct(private BetterReflectionNamedType $betterReflectionType, private bool $allowsNull)
    {
    }

    public function getName(): string
    {
        return $this->betterReflectionType->getName();
    }

    public function __toString(): string
    {
        $type = strtolower($this->betterReflectionType->getName());

        if (
            ! $this->allowsNull
            || $type === 'mixed'
            || $type === 'null'
        ) {
            return $this->betterReflectionType->__toString();
        }

        return '?' . $this->betterReflectionType->__toString();
    }

    public function allowsNull(): bool
    {
        return $this->allowsNull;
    }

    public function isBuiltin(): bool
    {
        $type = strtolower($this->betterReflectionType->getName());

        if ($type === 'self' || $type === 'parent' || $type === 'static') {
            return false;
        }

        return $this->betterReflectionType->isBuiltin();
    }
}
