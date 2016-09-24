<?php

namespace BetterReflection\SourceLocator\Exception;

class InvalidDirectory extends \RuntimeException
{
    /**
     * @param $nonDirectory
     * @return InvalidDirectory
     */
    public static function fromNonDirectory($nonDirectory)
    {
        return new InvalidDirectory(sprintf('%s is not a directory', $nonDirectory));
    }

    /**
     * @param $nonStringValue
     * @return InvalidDirectory
     */
    public static function fromNonStringValue($nonStringValue)
    {
        $foundType = null;
        switch (true) {
            case is_object($nonStringValue) :
                $foundType = sprintf('class %s', get_class($nonStringValue));
                break;
            case is_bool($nonStringValue) :
                $foundType = 'boolean';
                break;
            case is_null($nonStringValue) :
                $foundType = 'null';
                break;
            case is_int($nonStringValue) :
                $foundType = 'integer';
                break;
            case is_double($nonStringValue) :
                $foundType = 'double';
                break;
            case is_array($nonStringValue) :
                $foundType = 'array';
                break;
            default :
                $foundType = 'unknown type';
        }
        return new InvalidDirectory(sprintf('Expected string type of directory, %s given', $foundType));
    }
}