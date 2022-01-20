<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Util;

use function preg_match;
use function str_replace;

use const DIRECTORY_SEPARATOR;

class FileHelper
{
    public static function normalizeWindowsPath(string $path): string
    {
        return str_replace('\\', '/', $path);
    }

    public static function normalizeSystemPath(string $originalPath): string
    {
        $path = self::normalizeWindowsPath($originalPath);
        preg_match('~^([a-z]+)\\:\\/\\/(.+)~', $path, $matches);
        $scheme = null;
        if ($matches !== []) {
            [, $scheme, $path] = $matches;
        }

        if (DIRECTORY_SEPARATOR === '\\') {
            $path = str_replace('/', DIRECTORY_SEPARATOR, $path);
        }

        return ($scheme !== null ? $scheme . '://' : '') . $path;
    }
}
