<?php
declare(strict_types=1);

namespace Rector\BetterReflection\Reflection\Exception;

use Rector\BetterReflection\Reflection\ReflectionClass;
use UnexpectedValueException;

class NotAnInterfaceReflection extends UnexpectedValueException
{
    public static function fromReflectionClass(ReflectionClass $class) : self
    {
        $type = 'class';

        if ($class->isTrait()) {
            $type = 'trait';
        }

        return new self(\sprintf('Provided node "%s" is not interface, but "%s"', $class->getName(), $type));
    }
}
