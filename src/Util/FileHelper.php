<?php
declare(strict_types=1);

namespace Rector\BetterReflection\Util;

class FileHelper
{
    public static function normalizeWindowsPath(string $path) : string
    {
        return \str_replace('\\', '/', $path);
    }
}
