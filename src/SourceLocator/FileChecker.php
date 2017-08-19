<?php
declare(strict_types=1);

namespace Roave\BetterReflection\SourceLocator;

use Roave\BetterReflection\SourceLocator\Exception\InvalidFileLocation;

class FileChecker
{

    /**
     * @param string $filename
     *
     * @throws \Roave\BetterReflection\SourceLocator\Exception\InvalidFileLocation
     */
    public static function checkFile(string $filename) : void
    {
        if (empty($filename)) {
            throw new InvalidFileLocation('Filename was empty');
        }

        if (!file_exists($filename)) {
            throw new InvalidFileLocation('File does not exist');
        }

        if (!is_readable($filename)) {
            throw new InvalidFileLocation('File is not readable');
        }

        if (!is_file($filename)) {
            throw new InvalidFileLocation('Is not a file: ' . $filename);
        }
    }
}
