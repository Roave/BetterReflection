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

    public function testSystemWindowsPathWithProtocol() : void
    {
        if (DIRECTORY_SEPARATOR !== '\\') {
            $this->markTestSkipped('Test runs only on Windows');
        }

        $path = 'phar://C:/Users/ondrej/phpstan.phar/src/TrinaryLogic.php';
        self::assertSame(
            'phar://C:\Users\ondrej\phpstan.phar\src\TrinaryLogic.php',
            FileHelper::normalizeSystemPath($path),
        );
    }
}
