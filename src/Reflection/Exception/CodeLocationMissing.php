<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection\Exception;

use RuntimeException;

final class CodeLocationMissing extends RuntimeException
{
    public static function create(): self
    {
        return new self('Code location is missing');
    }
}
