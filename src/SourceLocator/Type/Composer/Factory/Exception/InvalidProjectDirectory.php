<?php

declare(strict_types=1);

namespace Roave\BetterReflection\SourceLocator\Type\Composer\Factory\Exception;

use InvalidArgumentException;

final class InvalidProjectDirectory extends InvalidArgumentException implements Exception
{
    public static function atPath(string $path) : self
    {
        return new self(sprintf(
            'Could not locate project directory "%s"',
            $path
        ));
    }
}
