<?php

namespace BetterReflection\SourceLocator\Exception;

class InvalidDirectory extends \RuntimeException
{
    /**
     * @param $nonDirectory string
     * @return InvalidDirectory
     */
    public static function fromNonDirectory($nonDirectory)
    {
        if (!file_exists($nonDirectory)) {
            return new InvalidDirectory(sprintf('%s is not exists', $nonDirectory));
        } else {
            return new InvalidDirectory(sprintf('%s is must to be a directory not a file', $nonDirectory));
        }
    }

    /**
     * @param $nonStringValue mixed
     * @return InvalidDirectory
     */
    public static function fromNonStringValue($nonStringValue)
    {
        $type = is_object($nonStringValue) ? get_class($nonStringValue) : gettype($nonStringValue) ;
        return new InvalidDirectory(sprintf('Expected string type of directory, %s given', $type));
    }
}