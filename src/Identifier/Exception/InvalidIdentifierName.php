<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Identifier\Exception;

use InvalidArgumentException;

class InvalidIdentifierName extends InvalidArgumentException
{
    public static function fromInvalidName(string $name) : self
    {
        return new self(\sprintf('Invalid identifier name "%s"', $name));
    }
}
