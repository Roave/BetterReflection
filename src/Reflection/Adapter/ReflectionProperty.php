<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection\Adapter;

use ArgumentCountError;
use OutOfBoundsException;
use PropertyHookType;
use ReflectionException as CoreReflectionException;
use ReflectionMethod as CoreReflectionMethod;
use ReflectionProperty as CoreReflectionProperty;
use Roave\BetterReflection\Reflection\Adapter\Exception\NotImplemented;
use Roave\BetterReflection\Reflection\Exception\NoObjectProvided;
use Roave\BetterReflection\Reflection\Exception\NotAnObject;
use Roave\BetterReflection\Reflection\ReflectionAttribute as BetterReflectionAttribute;
use Roave\BetterReflection\Reflection\ReflectionMethod as BetterReflectionMethod;
use Roave\BetterReflection\Reflection\ReflectionProperty as BetterReflectionProperty;
use Roave\BetterReflection\Reflection\ReflectionPropertyHookType as BetterReflectionPropertyHookType;
use Throwable;
use TypeError;
use ValueError;

use function array_map;
use function gettype;
use function sprintf;

/** @psalm-suppress PropertyNotSetInConstructor */
final class ReflectionProperty extends CoreReflectionProperty
{
    /**
     * @internal
     *
     * @see CoreReflectionProperty::IS_FINAL
     */
    public const IS_FINAL_COMPATIBILITY = 32;

    /**
     * @internal
     *
     * @see CoreReflectionProperty::IS_ABSTRACT
     */
    public const IS_ABSTRACT_COMPATIBILITY = 64;

    /**
     * @internal
     *
     * @see CoreReflectionProperty::IS_VIRTUAL
     */
    public const IS_VIRTUAL_COMPATIBILITY = 512;

    /**
     * @internal
     *
     * @see CoreReflectionProperty::IS_PROTECTED_SET
     */
    public const IS_PROTECTED_SET_COMPATIBILITY = 2048;

    /**
     * @internal
     *
     * @see CoreReflectionProperty::IS_PRIVATE_SET
     */
    public const IS_PRIVATE_SET_COMPATIBILITY = 4096;

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

    public function setRawValueWithoutLazyInitialization(object $object, mixed $value): never
    {
        throw Exception\NotImplementedBecauseItTriggersAutoloading::create();
    }

    /** @return never */
    public function isLazy(object $object): bool
    {
        throw Exception\NotImplementedBecauseItTriggersAutoloading::create();
    }

    public function skipLazyInitialization(object $object): never
    {
        throw Exception\NotImplementedBecauseItTriggersAutoloading::create();
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
    public function isPrivateSet(): bool
    {
        return $this->betterReflectionProperty->isPrivateSet();
    }

    /** @psalm-mutation-free */
    public function isProtected(): bool
    {
        return $this->betterReflectionProperty->isProtected();
    }

    /** @psalm-mutation-free */
    public function isProtectedSet(): bool
    {
        return $this->betterReflectionProperty->isProtectedSet();
    }

    /** @psalm-mutation-free */
    public function isStatic(): bool
    {
        return $this->betterReflectionProperty->isStatic();
    }

    /** @psalm-mutation-free */
    public function isFinal(): bool
    {
        return $this->betterReflectionProperty->isFinal();
    }

    /** @psalm-mutation-free */
    public function isAbstract(): bool
    {
        return $this->betterReflectionProperty->isAbstract();
    }

    /** @psalm-mutation-free */
    public function isDefault(): bool
    {
        return $this->betterReflectionProperty->isDefault();
    }

    /** @psalm-mutation-free */
    public function isDynamic(): bool
    {
        return $this->betterReflectionProperty->isDynamic();
    }

    public function isReadable(string|null $scope, object|null $object = null): bool
    {
        throw new NotImplemented('Not implemented');
    }

    public function isWritable(string|null $scope, object|null $object = null): bool
    {
        throw new NotImplemented('Not implemented');
    }

    /**
     * @return int-mask-of<self::IS_*>
     *
     * @psalm-mutation-free
     */
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

    /** @psalm-mutation-free */
    public function isVirtual(): bool
    {
        return $this->betterReflectionProperty->isVirtual();
    }

    public function hasHooks(): bool
    {
        return $this->betterReflectionProperty->hasHooks();
    }

    public function hasHook(PropertyHookType $type): bool
    {
        return $this->betterReflectionProperty->hasHook(BetterReflectionPropertyHookType::fromCoreReflectionPropertyHookType($type));
    }

    public function getHook(PropertyHookType $type): ReflectionMethod|null
    {
        $hook = $this->betterReflectionProperty->getHook(BetterReflectionPropertyHookType::fromCoreReflectionPropertyHookType($type));
        if ($hook === null) {
            return null;
        }

        return new ReflectionMethod($hook);
    }

    /** @return array{get?: ReflectionMethod, set?: ReflectionMethod} */
    public function getHooks(): array
    {
        return array_map(
            static fn (BetterReflectionMethod $betterReflectionMethod): CoreReflectionMethod => new ReflectionMethod($betterReflectionMethod),
            $this->betterReflectionProperty->getHooks(),
        );
    }

    public function getSettableType(): ReflectionUnionType|ReflectionNamedType|ReflectionIntersectionType|null
    {
        $setHook = $this->betterReflectionProperty->getHook(BetterReflectionPropertyHookType::Set);
        if ($setHook !== null) {
            return ReflectionType::fromTypeOrNull($setHook->getParameters()[0]->getType());
        }

        if ($this->isVirtual()) {
            return new ReflectionNamedType('never');
        }

        return $this->getType();
    }

    /** @return never */
    public function getRawValue(object $object): mixed
    {
        throw Exception\NotImplementedBecauseItTriggersAutoloading::create();
    }

    public function setRawValue(object $object, mixed $value): void
    {
        if ($this->hasHooks()) {
            throw Exception\NotImplementedBecauseItTriggersAutoloading::create();
        }

        $this->setValue($object, $value);
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

    public function getMangledName(): string
    {
        if ($this->betterReflectionProperty->isPrivate()) {
            return sprintf(
                "\0%s\0%s",
                $this->betterReflectionProperty->getDeclaringClass()->getName(),
                $this->betterReflectionProperty->getName(),
            );
        }

        if ($this->betterReflectionProperty->isProtected()) {
            return sprintf(
                "\0*\0%s",
                $this->betterReflectionProperty->getName(),
            );
        }

        return $this->betterReflectionProperty->getName();
    }
}
