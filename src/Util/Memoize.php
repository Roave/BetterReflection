<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Util;

use Closure;

/**
 * @internal do not touch: you have been warned.
 *
 * @template T
 * @psalm-immutable this context is a pure snapshot of a pure function, therefore transitively pure
 */
final class Memoize
{
    /**
     * @var T
     * @psalm-suppress PropertyNotSetInConstructor
     * @phpstan-ignore property.uninitializedReadonly
     */
    private readonly mixed $cached;

    /** @var (pure-Closure(): T)|null */
    private Closure|null $cb;

    /** @param pure-Closure(): T $cb */
    public function __construct(Closure $cb)
    {
        $this->cb = $cb;
    }

    /** @return T */
    public function get(): mixed
    {
        if ($this->cb) {
            /** @psalm-suppress InaccessibleProperty */
            /** @phpstan-ignore property.readOnlyAssignNotInConstructor */
            $this->cached = ($this->cb)();
            /** @psalm-suppress InaccessibleProperty */
            $this->cb = null;
        }

        return $this->cached;
    }
}
