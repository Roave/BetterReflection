<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\SourceLocator;

use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\SourceLocator\Exception\InvalidFileLocation;
use Roave\BetterReflection\SourceLocator\FileChecker;
use function chmod;
use function fileperms;
use function sprintf;
use function strpos;
use const PHP_OS;

/**
 * @covers \Roave\BetterReflection\SourceLocator\FileChecker
 */
class FileCheckerTest extends TestCase
{
    public function testCheckFileThrowsExceptionIfEmptyFileGiven() : void
    {
        $this->expectException(InvalidFileLocation::class);
        $this->expectExceptionMessage('Filename was empty');
        FileChecker::assertReadableFile('');
    }

    public function testCheckFileThrowsExceptionIfFileDoesNotExist() : void
    {
        $this->expectException(InvalidFileLocation::class);
        $this->expectExceptionMessage('File "sdklfjdfslsdfhlkjsdglkjsdflgkj" does not exist');
        FileChecker::assertReadableFile('sdklfjdfslsdfhlkjsdglkjsdflgkj');
    }

    public function testCheckFileThrowsExceptionIfFileIsNotAFile() : void
    {
        $this->expectException(InvalidFileLocation::class);
        $this->expectExceptionMessage('"' . __DIR__ . '" is not a file');
        FileChecker::assertReadableFile(__DIR__);
    }

    public function testCheckFileThrowsExceptionIfFileIsNotReadable() : void
    {
        if (strpos(PHP_OS, 'WIN') === 0) {
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
