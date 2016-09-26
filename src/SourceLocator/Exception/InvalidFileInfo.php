<?php

namespace BetterReflection\SourceLocator\Exception;

class InvalidFileInfo extends \RuntimeException
{
    /**
     * @param mixed $nonSplFileInfo
     *
     * @return InvalidFileInfo
     */
    public static function fromNonSplFileInfo($nonSplFileInfo)
    {
        return new self(sprintf(
            'Expected an iterator of SplFileInfo instances, %s given instead',
            is_object($nonSplFileInfo) ? get_class($nonSplFileInfo) : gettype($nonSplFileInfo)
        ));
    }
}
