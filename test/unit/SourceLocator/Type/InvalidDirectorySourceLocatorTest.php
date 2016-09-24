<?php

namespace BetterReflectionTest\SourceLocator\Type;

use BetterReflection\SourceLocator\Exception\InvalidDirectory;
use BetterReflection\SourceLocator\Type\DirectorySourceLocator;

/**
 * @covers \BetterReflection\SourceLocator\Type\DirectorySourceLocator
 * @covers \BetterReflection\SourceLocator\Exception\InvalidDirectory
 */
class InvalidDirectorySourceLocatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var string
     */
    private $directoryToScan = __DIR__ . '/../../Assets/DirectoryScannerAssets';

    public function testInvalidDirectory()
    {
        $this->expectException(InvalidDirectory::class);
        new DirectorySourceLocator([substr($this->directoryToScan, 0, strlen($this->directoryToScan)-1)]);
    }

    public function testInvalidIntegerDirectory()
    {
        $this->expectException(InvalidDirectory::class);
        new DirectorySourceLocator([$this->directoryToScan, 1]);
    }

    public function testInvalidBooleanDirectory()
    {
        $this->expectException(InvalidDirectory::class);
        new DirectorySourceLocator([$this->directoryToScan, true]);
    }

    public function testInvalidObjectDirectory()
    {
        $this->expectException(InvalidDirectory::class);
        new DirectorySourceLocator([$this->directoryToScan, new \stdClass()]);
    }

    public function testInvalidNullDirectory()
    {
        $this->expectException(InvalidDirectory::class);
        new DirectorySourceLocator([$this->directoryToScan, null]);
    }

    public function testExceptionMessage()
    {
        $e = InvalidDirectory::fromNonDirectory('testDir');
        $this->assertEquals(sprintf('%s is not exists', 'testDir'), $e->getMessage());

        $e = InvalidDirectory::fromNonDirectory(__FILE__);
        $this->assertEquals(sprintf('%s is must to be a directory not a file', __FILE__), $e->getMessage());

        $e = InvalidDirectory::fromNonStringValue(new \stdClass());
        $expected = 'Expected string type of directory, stdClass given';
        $this->assertEquals($expected, $e->getMessage());

        $e = InvalidDirectory::fromNonStringValue(true);
        $expected = 'Expected string type of directory, boolean given';
        $this->assertEquals($expected, $e->getMessage());

        $e = InvalidDirectory::fromNonStringValue(null);
        $expected = 'Expected string type of directory, NULL given';
        $this->assertEquals($expected, $e->getMessage());

        $e = InvalidDirectory::fromNonStringValue(100);
        $expected = 'Expected string type of directory, integer given';
        $this->assertEquals($expected, $e->getMessage());

        $e = InvalidDirectory::fromNonStringValue(100.35);
        $expected = 'Expected string type of directory, double given';
        $this->assertEquals($expected, $e->getMessage());

        $e = InvalidDirectory::fromNonStringValue([100, 200]);
        $expected = 'Expected string type of directory, array given';
        $this->assertEquals($expected, $e->getMessage());
    }
}
