<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\SourceLocator;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\SourceLocator\Exception\InvalidFileLocation;
use Roave\BetterReflection\SourceLocator\FileChecker;
use Throwable;

use function chmod;
use function fileperms;
use function sprintf;
use function str_starts_with;

use const PHP_OS;

#[CoversClass(FileChecker::class)]
class FileCheckerTest extends TestCase
{
    public function testCheckFileThrowsExceptionIfFileDoesNotExist(): void
    {
        $this->expectException(InvalidFileLocation::class);
        $this->expectExceptionMessage('"sdklfjdfslsdfhlkjsdglkjsdflgkj" is not a file');
        FileChecker::assertReadableFile('sdklfjdfslsdfhlkjsdglkjsdflgkj');
    }

    public function testCheckFileThrowsExceptionIfFileIsNotAFile(): void
    {
        $this->expectException(InvalidFileLocation::class);
        $this->expectExceptionMessage('"' . __DIR__ . '" is not a file');
        FileChecker::assertReadableFile(__DIR__);
    }

    public function testCheckFileThrowsExceptionIfFileIsNotReadable(): void
    {
        if (str_starts_with(PHP_OS, 'WIN')) {
            self::markTestSkipped('It\'s not possible to change file mode on Windows');
        }

        $file = __DIR__ . '/../Fixture/NoNamespace.php';

        $originalPermission = fileperms($file);
        chmod($file, 0000);

        $this->expectException(InvalidFileLocation::class);
        $this->expectExceptionMessage(sprintf('File "%s" is not readable', $file));

        try {
            FileChecker::assertReadableFile($file);
        } catch (Throwable $e) {
            throw $e;
        } finally {
            chmod($file, $originalPermission);
        }
    }
}
