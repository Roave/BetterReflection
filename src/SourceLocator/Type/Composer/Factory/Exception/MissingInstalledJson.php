<?php

declare(strict_types=1);

namespace Roave\BetterReflection\SourceLocator\Type\Composer\Factory\Exception;

use UnexpectedValueException;
use function sprintf;

final class MissingInstalledJson extends UnexpectedValueException implements Exception
{
    public static function inProjectPath(string $path) : self
    {
        return new self(sprintf(
            'Could not locate a "vendor/composer/installed.json" file in "%s"',
            $path
        ));
    }
}
