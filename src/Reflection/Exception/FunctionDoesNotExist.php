<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection\Exception;

use RuntimeException;

use function sprintf;

class FunctionDoesNotExist extends RuntimeException
{
    public static function fromName(string $functionName): self
    {
        return new self(sprintf('Function "%s" cannot be used as the function is not loaded', $functionName));
    }
}
