<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection\Exception;

use Roave\BetterReflection\Reflection\ReflectionClass;
use UnexpectedValueException;

class NotAClassReflection extends UnexpectedValueException
{
    public static function fromReflectionClass(ReflectionClass $class) : self
    {
        $type = 'interface';

        if ($class->isTrait()) {
            $type = 'trait';
        }

        return new self(\sprintf('Provided node "%s" is not class, but "%s"', $class->getName(), $type));
    }
}
