<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection\Exception;

use InvalidArgumentException;

class NoObjectProvided extends InvalidArgumentException
{
    public static function create(): self
    {
        return new self('No object provided');
    }
}
