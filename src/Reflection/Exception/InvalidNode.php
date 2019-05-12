<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection\Exception;

use RuntimeException;

class InvalidNode extends RuntimeException
{
    public static function create() : self
    {
        return new self('Invalid node');
    }
}
