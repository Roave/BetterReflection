<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection\Adapter;

use ArgumentCountError;
use OutOfBoundsException;
use ReflectionException as CoreReflectionException;
use ReflectionProperty as CoreReflectionProperty;
use Roave\BetterReflection\Reflection\Exception\NoObjectProvided;
use Roave\BetterReflection\Reflection\Exception\NotAnObject;
use Roave\BetterReflection\Reflection\ReflectionAttribute as BetterReflectionAttribute;
use Roave\BetterReflection\Reflection\ReflectionProperty as BetterReflectionProperty;
use Throwable;
use TypeError;
use ValueError;

use function array_map;
use function gettype;
use function sprintf;

/** @psalm-suppress PropertyNotSetInConstructor */
final class ReflectionProperty extends CoreReflectionProperty
{
    public function __construct(private BetterReflectionProperty $betterReflectionProperty)
    {
        unset($this->name);
        unset($this->class);
    }

    /** @return non-empty-string */
    public function __toString(): string
    {
        return $this->betterReflectionProperty->__toString();
    }

    /** @psalm-mutation-free */
    public function getName(): string
    {
        return $this->betterReflectionProperty->getName();
    }

    public function getValue(object|null $object = null): mixed
    {
        try {
            return $this->betterReflectionProperty->getValue($object);
        } catch (NoObjectProvided) {
            return null;
        } catch (Throwable $e) {
            throw new CoreReflectionException($e->getMessage(), previous: $e);
        }
    }

    /** @psalm-suppress MethodSignatureMismatch */
    public function setValue(mixed $objectOrValue, mixed $value = null): void
    {
        try {
            $this->betterReflectionProperty->setValue($objectOrValue, $value);
        } catch (NoObjectProvided) {
            throw new ArgumentCountError('ReflectionProperty::setValue() expects exactly 2 arguments, 1 given');
        } catch (NotAnObject) {
            throw new TypeError(sprintf('ReflectionProperty::setValue(): Argument #1 ($objectOrValue) must be of type object, %s given', gettype($objectOrValue)));
        } catch (Throwable $e) {
            throw new CoreReflectionException($e->getMessage(), previous: $e);
        }
    }

    /** @psalm-mutation-free */
    public function hasType(): bool
    {
        return $this->betterReflectionProperty->hasType();
    }

    /** @psalm-mutation-free */
    public function getType(): ReflectionUnionType|ReflectionNamedType|ReflectionIntersectionType|null
    {
        return ReflectionType::fromTypeOrNull($this->betterReflectionProperty->getType());
    }

    /** @psalm-mutation-free */
    public function isPublic(): bool
    {
        return $this->betterReflectionProperty->isPublic();
    }

    /** @psalm-mutation-free */
    public function isPrivate(): bool
    {
        return $this->betterReflectionProperty->isPrivate();
    }

    /** @psalm-mutation-free */
    public function isProtected(): bool
    {
        return $this->betterReflectionProperty->isProtected();
    }

    /** @psalm-mutation-free */
    public function isStatic(): bool
    {
        return $this->betterReflectionProperty->isStatic();
    }

    /** @psalm-mutation-free */
    public function isDefault(): bool
    {
        return $this->betterReflectionProperty->isDefault();
    }

    /** @psalm-mutation-free */
    public function getModifiers(): int
    {
        return $this->betterReflectionProperty->getModifiers();
    }

    /** @psalm-mutation-free */
    public function getDeclaringClass(): ReflectionClass
    {
        return new ReflectionClass($this->betterReflectionProperty->getImplementingClass());
    }

    /** @psalm-mutation-free */
    public function getDocComment(): string|false
    {
        return $this->betterReflectionProperty->getDocComment() ?? false;
    }

    /**
     * @codeCoverageIgnore
     * @infection-ignore-all
     * @psalm-mutation-free
     */
    public function setAccessible(bool $accessible): void
    {
    }

    /** @psalm-mutation-free */
    public function hasDefaultValue(): bool
    {
        return $this->betterReflectionProperty->hasDefaultValue();
    }

    /** @psalm-mutation-free */
    public function getDefaultValue(): mixed
    {
        return $this->betterReflectionProperty->getDefaultValue();
    }

    public function isInitialized(object|null $object = null): bool
    {
        try {
            return $this->betterReflectionProperty->isInitialized($object);
        } catch (Throwable $e) {
            throw new CoreReflectionException($e->getMessage(), previous: $e);
        }
    }

    /** @psalm-mutation-free */
    public function isPromoted(): bool
    {
        return $this->betterReflectionProperty->isPromoted();
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
            $attributes = $this->betterReflectionProperty->getAttributesByInstance($name);
        } elseif ($name !== null) {
            $attributes = $this->betterReflectionProperty->getAttributesByName($name);
        } else {
            $attributes = $this->betterReflectionProperty->getAttributes();
        }

        return array_map(static fn (BetterReflectionAttribute $betterReflectionAttribute): ReflectionAttribute => new ReflectionAttribute($betterReflectionAttribute), $attributes);
    }

    /** @psalm-mutation-free */
    public function isReadOnly(): bool
    {
        return $this->betterReflectionProperty->isReadOnly();
    }

    public function __get(string $name): mixed
    {
        if ($name === 'name') {
            return $this->betterReflectionProperty->getName();
        }

        if ($name === 'class') {
            return $this->betterReflectionProperty->getImplementingClass()->getName();
        }

        throw new OutOfBoundsException(sprintf('Property %s::$%s does not exist.', self::class, $name));
    }
}
