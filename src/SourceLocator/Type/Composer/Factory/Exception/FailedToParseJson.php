<?php

declare(strict_types=1);

namespace Roave\BetterReflection\SourceLocator\Type\Composer\Factory\Exception;

use UnexpectedValueException;

final class FailedToParseJson extends UnexpectedValueException implements Exception
{
    // @TODO drop and force PHP 7?
    public static function inFile(string $file) : self
    {
        return new self(sprintf(
            'Could not parse JSON file "%s"',
            $file
        ));
    }
}
