<?php

namespace Roave\BetterReflection\Reflection\Exception;

class Uncloneable extends \LogicException
{
    public static function fromClass(string $className) : self
    {
        return new self('Trying to clone an uncloneable object of class ' . $className);
    }
}
