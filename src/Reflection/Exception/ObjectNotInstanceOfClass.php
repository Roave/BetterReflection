<?php
declare(strict_types=1);

namespace Rector\BetterReflection\Reflection\Exception;

use InvalidArgumentException;

class ObjectNotInstanceOfClass extends InvalidArgumentException
{
    public static function fromClassName(string $className) : self
    {
        return new self(\sprintf('Object is not instance of class "%s"', $className));
    }
}
