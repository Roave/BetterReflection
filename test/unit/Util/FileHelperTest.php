<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Util;

use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\Util\FileHelper;

/** @covers \Roave\BetterReflection\Util\FileHelper */
class FileHelperTest extends TestCase
{
    public function testNormalizeWindowsPath(): void
    {
        self::assertSame('directory/foo/boo/file.php', FileHelper::normalizeWindowsPath('directory\\foo/boo\\file.php'));
        self::assertSame('directory/foo/boo/file.php', FileHelper::normalizeWindowsPath('directory/foo/boo/file.php'));
    }

    /** @return list<array{0: string, 1: string}> */
    public function dataNormalizeSystemPath(): array
    {
        return [
            ['directory\\foo/boo\\foo/file.php', 'directory/foo/boo/foo/file.php'],
            ['phar://C:/Users/ondrej/phpstan.phar/src/TrinaryLogic.php', 'phar://C:/Users/ondrej/phpstan.phar/src/TrinaryLogic.php'],
            ['C:/Users/ondrej/phpstan.phar/src/TrinaryLogic.php', 'C:/Users/ondrej/phpstan.phar/src/TrinaryLogic.php'],
            ['/directory/strange-path-c://file.php', '/directory/strange-path-c://file.php'],
        ];
    }

    /**
     * @dataProvider dataNormalizeSystemPath
     * @requires OS Linux
     */
    public function testSystemWindowsPath(string $path, string $expectedPath): void
    {
        self::assertSame($expectedPath, FileHelper::normalizeSystemPath($path));
    }

    /** @requires OSFAMILY Windows */
    public function testSystemWindowsPathOnWindows(): void
    {
        $path = 'phar://C:/Users/ondrej/phpstan.phar/src/TrinaryLogic.php';
        self::assertSame(
            'phar://C:\Users\ondrej\phpstan.phar\src\TrinaryLogic.php',
            FileHelper::normalizeSystemPath($path),
        );
    }
}
