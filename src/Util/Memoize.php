<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Util;

/** @template T */
final class Memoize {
    /**
     * @var T
     * @psalm-suppress PossiblyNullPropertyAssignmentValue
     */
    private mixed $cached = null;

    /**
     * @var callable(): T
     */
    private  $fn;

    /** @param callable(): T $fn */
    public function __construct(callable $fn)
    {
        $this->fn = $fn;
    }

    /**
     * @return T
     */
    public function memoize(): mixed {
        if ($this->cached === null) {
            $this->cached = ($this->fn)();
        }
        return $this->cached;
    }
}
