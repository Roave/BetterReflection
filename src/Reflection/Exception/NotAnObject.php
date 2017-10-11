<?php
declare(strict_types=1);

namespace Rector\BetterReflection\Reflection\Exception;

use InvalidArgumentException;

class NotAnObject extends InvalidArgumentException
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
