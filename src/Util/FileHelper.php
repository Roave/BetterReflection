<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Util;

use function assert;
use function preg_match;
use function sprintf;
use function str_replace;

use const DIRECTORY_SEPARATOR;

final class FileHelper
{
    /**
     * @param non-empty-string $path
     *
     * @return non-empty-string
     *
     * @psalm-pure
     */
    public static function normalizeWindowsPath(string $path): string
    {
        $path = str_replace('\\', '/', $path);
        assert($path !== '');

        return $path;
    }

    /**
     * @param non-empty-string $originalPath
     *
     * @return non-empty-string
     *
     * @psalm-pure
     */
    public static function normalizeSystemPath(string $originalPath): string
    {
        $path = self::normalizeWindowsPath($originalPath);
        preg_match('~^([a-z]+)\\:\\/\\/(.+)~', $path, $matches);
        $scheme = null;
        if ($matches !== []) {
            [, $scheme, $path] = $matches;
        }

        // @infection-ignore-all Identical Needed only on Windows
        if (DIRECTORY_SEPARATOR === '\\') {
            // @infection-ignore-all UnwrapStrReplace Needed only on Windows
            $path = str_replace('/', DIRECTORY_SEPARATOR, $path);
        }

        assert($path !== '');

        return ($scheme !== null ? sprintf('%s://', $scheme) : '') . $path;
    }
}
