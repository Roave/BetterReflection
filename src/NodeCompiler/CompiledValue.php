<?php

declare(strict_types=1);

namespace Roave\BetterReflection\NodeCompiler;

/** @internal */
class CompiledValue
{
    public function __construct(public mixed $value, public string|null $constantName = null)
    {
    }
}
