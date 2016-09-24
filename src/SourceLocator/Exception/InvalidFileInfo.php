<?php

namespace BetterReflection\SourceLocator\Exception;

class InvalidFileInfo extends \RuntimeException
{
    /**
     * @param $nonSplFileInfo mixed
     * @return InvalidFileInfo
     */
    public static function fromNonSplFileInfo($nonSplFileInfo)
    {
        $type = is_object($nonSplFileInfo) ? get_class($nonSplFileInfo) : gettype($nonSplFileInfo) ;
        return new InvalidFileInfo(sprintf('Expected \\SplFileInfo type of Iterator\'s items, %s given', $type));
    }
}