<?php

namespace Roave\BetterReflection\Reflection\Exception;

class Uncloneable extends \LogicException
{
    public static function fromClass($className)
    {
        return new self('Trying to clone an uncloneable object of class ' . $className);
    }
}
