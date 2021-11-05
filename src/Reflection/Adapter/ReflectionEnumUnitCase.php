<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection\Adapter;

use ReflectionEnumUnitCase as CoreReflectionEnumUnitCase;
use Roave\BetterReflection\Reflection\ReflectionAttribute as BetterReflectionAttribute;
use Roave\BetterReflection\Reflection\ReflectionEnumCase as BetterReflectionEnumCase;
use UnitEnum;

use function array_map;

final class ReflectionEnumUnitCase extends CoreReflectionEnumUnitCase
{
    public function __construct(private BetterReflectionEnumCase $betterReflectionEnumCase)
    {
    }

    /**
     * Get the name of the reflection (e.g. if this is a ReflectionClass this
     * will be the class name).
     */
    public function getName(): string
    {
        return $this->betterReflectionEnumCase->getName();
    }

    public function getValue(): UnitEnum
    {
        throw new Exception\NotImplemented('Not implemented');
    }

    public function isPublic(): bool
    {
        return true;
    }

    public function isPrivate(): bool
    {
        return false;
    }

    public function isProtected(): bool
    {
        return false;
    }

    public function getModifiers(): int
    {
        return self::IS_PUBLIC;
    }

    public function getDeclaringClass(): ReflectionClass
    {
        return new ReflectionClass($this->betterReflectionEnumCase->getDeclaringClass());
    }

    public function getDocComment(): string|false
    {
        return $this->betterReflectionEnumCase->getDocComment() ?: false;
    }

    public function __toString(): string
    {
        return $this->betterReflectionEnumCase->__toString();
    }

    /**
     * @param class-string|null $name
     *
     * @return list<ReflectionAttribute>
     */
    public function getAttributes(?string $name = null, int $flags = 0): array
    {
        if ($name !== null && $flags & ReflectionAttribute::IS_INSTANCEOF) {
            $attributes = $this->betterReflectionEnumCase->getAttributesByInstance($name);
        } elseif ($name !== null) {
            $attributes = $this->betterReflectionEnumCase->getAttributesByName($name);
        } else {
            $attributes = $this->betterReflectionEnumCase->getAttributes();
        }

        return array_map(static fn (BetterReflectionAttribute $betterReflectionAttribute): ReflectionAttribute => new ReflectionAttribute($betterReflectionAttribute), $attributes);
    }

    public function isFinal(): bool
    {
        return true;
    }

    public function isEnumCase(): bool
    {
        return true;
    }

    public function getEnum(): ReflectionEnum
    {
        return new ReflectionEnum($this->betterReflectionEnumCase->getDeclaringEnum());
    }
}
