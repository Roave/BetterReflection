<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection\Adapter;

use OutOfBoundsException;
use ReflectionEnumBackedCase as CoreReflectionEnumBackedCase;
use Roave\BetterReflection\Reflection\ReflectionAttribute as BetterReflectionAttribute;
use Roave\BetterReflection\Reflection\ReflectionEnumCase as BetterReflectionEnumCase;
use UnitEnum;
use ValueError;

use function array_map;
use function sprintf;

final class ReflectionEnumBackedCase extends CoreReflectionEnumBackedCase
{
    public function __construct(private BetterReflectionEnumCase $betterReflectionEnumCase)
    {
        unset($this->name);
        unset($this->class);
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
    public function getAttributes(string|null $name = null, int $flags = 0): array
    {
        if ($flags !== 0 && $flags !== ReflectionAttribute::IS_INSTANCEOF) {
            throw new ValueError('Argument #2 ($flags) must be a valid attribute filter flag');
        }

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

    public function getBackingValue(): int|string
    {
        return $this->betterReflectionEnumCase->getValue();
    }

    public function __get(string $name): mixed
    {
        if ($name === 'name') {
            return $this->betterReflectionEnumCase->getName();
        }

        if ($name === 'class') {
            return $this->betterReflectionEnumCase->getDeclaringClass()->getName();
        }

        throw new OutOfBoundsException(sprintf('Property %s::$%s does not exist.', self::class, $name));
    }
}
