<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection\Adapter;

use OutOfBoundsException;
use ReflectionClassConstant as CoreReflectionClassConstant;
use Roave\BetterReflection\Reflection\ReflectionAttribute as BetterReflectionAttribute;
use Roave\BetterReflection\Reflection\ReflectionClassConstant as BetterReflectionClassConstant;
use Roave\BetterReflection\Reflection\ReflectionEnumCase as BetterReflectionEnumCase;
use ValueError;

use function array_map;
use function constant;
use function sprintf;

final class ReflectionClassConstant extends CoreReflectionClassConstant
{
    public function __construct(private BetterReflectionClassConstant|BetterReflectionEnumCase $betterClassConstantOrEnumCase)
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
        return $this->betterClassConstantOrEnumCase->getName();
    }

    /**
     * Returns constant value
     */
    public function getValue(): mixed
    {
        if ($this->betterClassConstantOrEnumCase instanceof BetterReflectionEnumCase) {
            return constant(sprintf('%s::%s', $this->betterClassConstantOrEnumCase->getDeclaringClass()->getName(), $this->betterClassConstantOrEnumCase->getName()));
        }

        return $this->betterClassConstantOrEnumCase->getValue();
    }

    /**
     * Constant is public
     */
    public function isPublic(): bool
    {
        if ($this->betterClassConstantOrEnumCase instanceof BetterReflectionEnumCase) {
            return true;
        }

        return $this->betterClassConstantOrEnumCase->isPublic();
    }

    /**
     * Constant is private
     */
    public function isPrivate(): bool
    {
        if ($this->betterClassConstantOrEnumCase instanceof BetterReflectionEnumCase) {
            return false;
        }

        return $this->betterClassConstantOrEnumCase->isPrivate();
    }

    /**
     * Constant is protected
     */
    public function isProtected(): bool
    {
        if ($this->betterClassConstantOrEnumCase instanceof BetterReflectionEnumCase) {
            return false;
        }

        return $this->betterClassConstantOrEnumCase->isProtected();
    }

    /**
     * Returns a bitfield of the access modifiers for this constant
     */
    public function getModifiers(): int
    {
        if ($this->betterClassConstantOrEnumCase instanceof BetterReflectionEnumCase) {
            return CoreReflectionClassConstant::IS_PUBLIC;
        }

        return $this->betterClassConstantOrEnumCase->getModifiers();
    }

    /**
     * Get the declaring class
     */
    public function getDeclaringClass(): ReflectionClass
    {
        if ($this->betterClassConstantOrEnumCase instanceof BetterReflectionEnumCase) {
            return new ReflectionClass($this->betterClassConstantOrEnumCase->getDeclaringClass());
        }

        return new ReflectionClass($this->betterClassConstantOrEnumCase->getImplementingClass());
    }

    /**
     * Returns the doc comment for this constant
     */
    public function getDocComment(): string|false
    {
        return $this->betterClassConstantOrEnumCase->getDocComment() ?: false;
    }

    /**
     * To string
     *
     * @link https://php.net/manual/en/reflector.tostring.php
     */
    public function __toString(): string
    {
        return $this->betterClassConstantOrEnumCase->__toString();
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
            $attributes = $this->betterClassConstantOrEnumCase->getAttributesByInstance($name);
        } elseif ($name !== null) {
            $attributes = $this->betterClassConstantOrEnumCase->getAttributesByName($name);
        } else {
            $attributes = $this->betterClassConstantOrEnumCase->getAttributes();
        }

        return array_map(static fn (BetterReflectionAttribute $betterReflectionAttribute): ReflectionAttribute => new ReflectionAttribute($betterReflectionAttribute), $attributes);
    }

    public function isFinal(): bool
    {
        if ($this->betterClassConstantOrEnumCase instanceof BetterReflectionEnumCase) {
            return true;
        }

        return $this->betterClassConstantOrEnumCase->isFinal();
    }

    public function isEnumCase(): bool
    {
        return $this->betterClassConstantOrEnumCase instanceof BetterReflectionEnumCase;
    }

    public function __get(string $name): mixed
    {
        if ($name === 'name') {
            return $this->betterClassConstantOrEnumCase->getName();
        }

        if ($name === 'class') {
            return $this->getDeclaringClass()->getName();
        }

        throw new OutOfBoundsException(sprintf('Property %s::$%s does not exist.', self::class, $name));
    }
}
