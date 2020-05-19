<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Util;

use function str_replace;
use const DIRECTORY_SEPARATOR;

class FileHelper
{
    public static function normalizeWindowsPath(string $path) : string
    {
        return str_replace('\\', '/', $path);
    }

    public static function normalizeSystemPath(string $path) : string
    {
        $path = self::normalizeWindowsPath($path);

        return DIRECTORY_SEPARATOR === '\\'
            ? str_replace('/', '\\', $path)
            : $path;
    }
}
