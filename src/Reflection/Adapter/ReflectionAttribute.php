<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection\Adapter;

use Attribute;
use OutOfBoundsException;
use ReflectionAttribute as CoreReflectionAttribute;
use Roave\BetterReflection\Reflection\Adapter\Exception\NotImplemented;
use Roave\BetterReflection\Reflection\ReflectionAttribute as BetterReflectionAttribute;

use function sprintf;

/** @template-extends CoreReflectionAttribute<object> */
final class ReflectionAttribute extends CoreReflectionAttribute
{
    public const TARGET_CONSTANT_COMPATIBILITY = 64;

    public function __construct(private BetterReflectionAttribute $betterReflectionAttribute)
    {
        unset($this->name);
    }

    /** @psalm-mutation-free */
    public function getName(): string
    {
        return $this->betterReflectionAttribute->getName();
    }

    public function getShortName(): string
    {
        throw new NotImplemented('Not implemented');
    }

    public function getNamespaceName(): string
    {
        throw new NotImplemented('Not implemented');
    }

    public function inNamespace(): bool
    {
        throw new NotImplemented('Not implemented');
    }

    /**
     * @return int-mask-of<Attribute::TARGET_*>|self::TARGET_CONSTANT_COMPATIBILITY
     *
     * @psalm-mutation-free
     * @psalm-suppress ImplementedReturnTypeMismatch
     */
    public function getTarget(): int
    {
        return $this->betterReflectionAttribute->getTarget();
    }

    /** @psalm-mutation-free */
    public function isRepeated(): bool
    {
        return $this->betterReflectionAttribute->isRepeated();
    }

    /** @return array<int|string, mixed> */
    public function getArguments(): array
    {
        return $this->betterReflectionAttribute->getArguments();
    }

    /** @return never */
    public function newInstance(): object
    {
        throw Exception\NotImplementedBecauseItTriggersAutoloading::create();
    }

    /** @return non-empty-string */
    public function __toString(): string
    {
        return $this->betterReflectionAttribute->__toString();
    }

    public function __get(string $name): mixed
    {
        if ($name === 'name') {
            return $this->betterReflectionAttribute->getName();
        }

        throw new OutOfBoundsException(sprintf('Property %s::$%s does not exist.', self::class, $name));
    }
}
