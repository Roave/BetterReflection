<?php

declare(strict_types=1);

namespace Roave\BetterReflection\SourceLocator;

use Roave\BetterReflection\SourceLocator\Exception\InvalidFileLocation;

use function is_file;
use function is_readable;
use function sprintf;

class FileChecker
{
    /**
     * @param non-empty-string $filename
     *
     * @throws InvalidFileLocation
     */
    public static function assertReadableFile(string $filename): void
    {
        if (! is_file($filename)) {
            throw new InvalidFileLocation(sprintf('"%s" is not a file', $filename));
        }

        if (! is_readable($filename)) {
            throw new InvalidFileLocation(sprintf('File "%s" is not readable', $filename));
        }
    }
}
