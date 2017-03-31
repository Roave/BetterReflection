<?php

namespace Roave\BetterReflection\Reflection\Exception;

use Roave\BetterReflection\Reflection\ReflectionClass;

class NotAClassReflection extends \UnexpectedValueException
{
    /**
     * @param ReflectionClass $class
     *
     * @return self
     */
    public static function fromReflectionClass(ReflectionClass $class) : self
    {
        $type = 'interface';

        if ($class->isTrait()) {
            $type = 'trait';
        }

        return new self(sprintf('Provided node "%s" is not class, but "%s"', $class->getName(), $type));
    }
}
