<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection\Exception;

use InvalidArgumentException;

use function gettype;
use function sprintf;

class NotAnObject extends InvalidArgumentException
{
    public static function fromNonObject(mixed $nonObject): self
    {
        return new self(sprintf('Provided "%s" is not an object', gettype($nonObject)));
    }
}
