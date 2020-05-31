<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Util;

use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\Util\FileHelper;
use function strtr;
use const DIRECTORY_SEPARATOR;

/**
 * @covers \Roave\BetterReflection\Util\FileHelper
 */
class FileHelperTest extends TestCase
{
    public function testNormalizeWindowsPath() : void
    {
        self::assertSame('directory/foo/boo/file.php', FileHelper::normalizeWindowsPath('directory\\foo/boo\\file.php'));
        self::assertSame('directory/foo/boo/file.php', FileHelper::normalizeWindowsPath('directory/foo/boo/file.php'));
    }

    public function testSystemWindowsPath() : void
    {
        $path = 'directory\\foo/boo\\foo/file.php';

        self::assertSame(strtr($path, '\\/', DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR), FileHelper::normalizeSystemPath($path));
    }
}
