<?php
declare(strict_types=1);

namespace Roave\BetterReflection\Reflection\Exception;

class NotAnObject extends \InvalidArgumentException
{
    /**
     * @param mixed $nonObject
     *
     * @return self
     */
    public static function fromNonObject($nonObject) : self
    {
        return new self(\sprintf('Provided "%s" is not an object', \gettype($nonObject)));
    }
}
