<?php

declare(strict_types=1);

namespace Roave\BetterReflection\SourceLocator\Type\Composer\Factory\Exception;

use UnexpectedValueException;
use function sprintf;

final class FailedToParseJson extends UnexpectedValueException implements Exception
{
    public static function inFile(string $file) : self
    {
        return new self(sprintf(
            'Could not parse JSON file "%s"',
            $file
        ));
    }
}
