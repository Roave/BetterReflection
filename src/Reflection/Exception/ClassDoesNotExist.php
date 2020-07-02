<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection\Exception;

use Roave\BetterReflection\Reflection\Reflection;
use RuntimeException;

use function sprintf;

class ClassDoesNotExist extends RuntimeException
{
    public static function forDifferentReflectionType(Reflection $reflection): self
    {
        return new self(sprintf('The reflected type "%s" is not a class', $reflection->getName()));
    }
}
