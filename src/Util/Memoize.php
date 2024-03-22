<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Util;

use Closure;

/** * @template T * @internal do not touch: you have been warned. */
final class Memoize
{
    private readonly mixed $cached;

    /** @param pure-Closure(): T $cb */
    public function __construct(private Closure|null $cb)
    {
    }

    /** @return T */
    public function get(): mixed
    {
        if ($this->cb) {
            $this->cached = ($this->cb)();
            $this->cb     = null;
        }

        return $this->cached;
    }
}
