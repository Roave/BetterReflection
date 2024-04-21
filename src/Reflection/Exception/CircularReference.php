<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection\Exception;

use UnexpectedValueException;

use function sprintf;

final class CircularReference extends UnexpectedValueException
{
    public static function fromClassName(string $className): self
    {
        return new self(sprintf('Circular reference to class "%s"', $className));
    }
}
