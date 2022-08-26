<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection\Adapter;

use OutOfBoundsException;
use ReflectionClass as CoreReflectionClass;
use ReflectionFunctionAbstract as CoreReflectionFunctionAbstract;
use ReflectionParameter as CoreReflectionParameter;
use Roave\BetterReflection\Reflection\ReflectionAttribute as BetterReflectionAttribute;
use Roave\BetterReflection\Reflection\ReflectionMethod as BetterReflectionMethod;
use Roave\BetterReflection\Reflection\ReflectionParameter as BetterReflectionParameter;
use ValueError;

use function array_map;
use function sprintf;

/** @psalm-suppress MissingImmutableAnnotation */
final class ReflectionParameter extends CoreReflectionParameter
{
    public function __construct(private BetterReflectionParameter $betterReflectionParameter)
    {
        unset($this->name);
    }

    public function __toString(): string
    {
        return $this->betterReflectionParameter->__toString();
    }

    public function getName(): string
    {
        return $this->betterReflectionParameter->getName();
    }

    public function isPassedByReference(): bool
    {
        return $this->betterReflectionParameter->isPassedByReference();
    }

    public function canBePassedByValue(): bool
    {
        return $this->betterReflectionParameter->canBePassedByValue();
    }

    public function getDeclaringFunction(): CoreReflectionFunctionAbstract
    {
        $function = $this->betterReflectionParameter->getDeclaringFunction();

        if ($function instanceof BetterReflectionMethod) {
            return new ReflectionMethod($function);
        }

        return new ReflectionFunction($function);
    }

    public function getDeclaringClass(): CoreReflectionClass|null
    {
        $declaringClass = $this->betterReflectionParameter->getDeclaringClass();

        if ($declaringClass === null) {
            return null;
        }

        return new ReflectionClass($declaringClass);
    }

    public function getClass(): CoreReflectionClass|null
    {
        $class = $this->betterReflectionParameter->getClass();

        if ($class === null) {
            return null;
        }

        return new ReflectionClass($class);
    }

    public function isArray(): bool
    {
        return $this->betterReflectionParameter->isArray();
    }

    public function isCallable(): bool
    {
        return $this->betterReflectionParameter->isCallable();
    }

    public function allowsNull(): bool
    {
        return $this->betterReflectionParameter->allowsNull();
    }

    public function getPosition(): int
    {
        return $this->betterReflectionParameter->getPosition();
    }

    public function isOptional(): bool
    {
        return $this->betterReflectionParameter->isOptional();
    }

    public function isVariadic(): bool
    {
        return $this->betterReflectionParameter->isVariadic();
    }

    public function isDefaultValueAvailable(): bool
    {
        return $this->betterReflectionParameter->isDefaultValueAvailable();
    }

    public function getDefaultValue(): mixed
    {
        return $this->betterReflectionParameter->getDefaultValue();
    }

    public function isDefaultValueConstant(): bool
    {
        return $this->betterReflectionParameter->isDefaultValueConstant();
    }

    public function getDefaultValueConstantName(): string
    {
        return $this->betterReflectionParameter->getDefaultValueConstantName();
    }

    public function hasType(): bool
    {
        return $this->betterReflectionParameter->hasType();
    }

    public function getType(): ReflectionNamedType|ReflectionUnionType|ReflectionIntersectionType|ReflectionType|null
    {
        return ReflectionType::fromTypeOrNull($this->betterReflectionParameter->getType());
    }

    public function isPromoted(): bool
    {
        return $this->betterReflectionParameter->isPromoted();
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
            $attributes = $this->betterReflectionParameter->getAttributesByInstance($name);
        } elseif ($name !== null) {
            $attributes = $this->betterReflectionParameter->getAttributesByName($name);
        } else {
            $attributes = $this->betterReflectionParameter->getAttributes();
        }

        return array_map(static fn (BetterReflectionAttribute $betterReflectionAttribute): ReflectionAttribute => new ReflectionAttribute($betterReflectionAttribute), $attributes);
    }

    public function __get(string $name): mixed
    {
        if ($name === 'name') {
            return $this->betterReflectionParameter->getName();
        }

        throw new OutOfBoundsException(sprintf('Property %s::$%s does not exist.', self::class, $name));
    }
}
