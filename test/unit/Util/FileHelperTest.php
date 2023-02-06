<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Util;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresOperatingSystem;
use PHPUnit\Framework\Attributes\RequiresOperatingSystemFamily;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\Util\FileHelper;

#[CoversClass(FileHelper::class)]
class FileHelperTest extends TestCase
{
    public function testNormalizeWindowsPath(): void
    {
        self::assertSame('directory/foo/boo/file.php', FileHelper::normalizeWindowsPath('directory\\foo/boo\\file.php'));
        self::assertSame('directory/foo/boo/file.php', FileHelper::normalizeWindowsPath('directory/foo/boo/file.php'));
    }

    /** @return list<array{0: string, 1: string}> */
    public static function dataNormalizeSystemPath(): array
    {
        return [
            ['directory\\foo/boo\\foo/file.php', 'directory/foo/boo/foo/file.php'],
            ['phar://C:/Users/ondrej/phpstan.phar/src/TrinaryLogic.php', 'phar://C:/Users/ondrej/phpstan.phar/src/TrinaryLogic.php'],
            ['C:/Users/ondrej/phpstan.phar/src/TrinaryLogic.php', 'C:/Users/ondrej/phpstan.phar/src/TrinaryLogic.php'],
            ['/directory/strange-path-c://file.php', '/directory/strange-path-c://file.php'],
        ];
    }

    #[DataProvider('dataNormalizeSystemPath')]
    #[RequiresOperatingSystem('Linux')]
    public function testSystemWindowsPath(string $path, string $expectedPath): void
    {
        self::assertSame($expectedPath, FileHelper::normalizeSystemPath($path));
    }

    #[RequiresOperatingSystemFamily('Windows')]
    public function testSystemWindowsPathOnWindows(): void
    {
        $path = 'phar://C:/Users/ondrej/phpstan.phar/src/TrinaryLogic.php';
        self::assertSame(
            'phar://C:\Users\ondrej\phpstan.phar\src\TrinaryLogic.php',
            FileHelper::normalizeSystemPath($path),
        );
    }
}
