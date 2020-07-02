<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection\Exception;

use RuntimeException;

use function sprintf;

class PropertyDoesNotExist extends RuntimeException
{
    public static function fromName(string $propertyName): self
    {
        return new self(sprintf('Property "%s" does not exist', $propertyName));
    }
}
