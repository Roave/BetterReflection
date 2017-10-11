<?php
declare(strict_types=1);

namespace Rector\BetterReflection\SourceLocator;

use Rector\BetterReflection\SourceLocator\Exception\InvalidFileLocation;

class FileChecker
{
    /**
     * @param string $filename
     *
     * @throws \Rector\BetterReflection\SourceLocator\Exception\InvalidFileLocation
     */
    public static function assertReadableFile(string $filename) : void
    {
        if (empty($filename)) {
            throw new InvalidFileLocation('Filename was empty');
        }

        if ( ! \file_exists($filename)) {
            throw new InvalidFileLocation(\sprintf('File "%s" does not exist', $filename));
        }

        if ( ! \is_readable($filename)) {
            throw new InvalidFileLocation(\sprintf('File "%s" is not readable', $filename));
        }

        if ( ! \is_file($filename)) {
            throw new InvalidFileLocation(\sprintf('"%s" is not a file', $filename));
        }
    }
}
