<?php

namespace BetterReflection\Reflection\Exception;

class NotAString extends \InvalidArgumentException
{
    /**
     * @param mixed $nonString
     *
     * @return self
     */
    public static function fromNonString($nonString)
    {
        return new self(sprintf(
            'Provided "%s" is not a string',
            is_object($nonString) ? get_class($nonString) : gettype($nonString)
        ));
    }
}
