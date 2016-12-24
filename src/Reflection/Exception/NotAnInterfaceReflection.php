<?php

namespace Roave\BetterReflection\Reflection\Exception;

use Roave\BetterReflection\Reflection\ReflectionClass;

class NotAnInterfaceReflection extends \UnexpectedValueException
{
    /**
     * @param ReflectionClass $class
     *
     * @return self
     */
    public static function fromReflectionClass(ReflectionClass $class)
    {
        $type = 'class';

        if ($class->isTrait()) {
            $type = 'trait';
        }

        return new self(sprintf('Provided node "%s" is not interface, but "%s"', $class->getName(), $type));
    }
}
