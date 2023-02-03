<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection\Adapter;

use ReflectionAttribute as CoreReflectionAttribute;
use Roave\BetterReflection\Reflection\ReflectionAttribute as BetterReflectionAttribute;

/** @template-extends CoreReflectionAttribute<object> */
final class ReflectionAttribute extends CoreReflectionAttribute
{
    public function __construct(private BetterReflectionAttribute $betterReflectionAttribute)
    {
    }

    /** @psalm-mutation-free */
    public function getName(): string
    {
        return $this->betterReflectionAttribute->getName();
    }

    /** @psalm-mutation-free */
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

    public function newInstance(): object
    {
        throw new Exception\NotImplemented('Not implemented');
    }

    /** @return non-empty-string */
    public function __toString(): string
    {
        return $this->betterReflectionAttribute->__toString();
    }
}
