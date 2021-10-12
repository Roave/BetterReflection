<?php

declare(strict_types=1);

namespace Roave\BetterReflection\NodeCompiler;

/**
 * @internal
 */
class CompiledValue
{
    /**
     * @param scalar|array<scalar>|null $value
     */
    public function __construct(public string|int|float|bool|array|null $value, public ?string $constantName = null)
    {
    }
}
