<?php

namespace Roave\BetterReflection\Reflection\Exception;

class NotAnObject extends \InvalidArgumentException
{
    /**
     * @param mixed $nonObject
     *
     * @return self
     */
    public static function fromNonObject($nonObject)
    {
        return new self(sprintf('Provided "%s" is not an object', gettype($nonObject)));
    }
}
