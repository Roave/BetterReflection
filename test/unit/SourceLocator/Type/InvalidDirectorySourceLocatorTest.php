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

    /**
     * Make sure that $directoryToScan is a valid directory
     */
    public function testDirectoryToScan()
    {
        new DirectorySourceLocator([$this->directoryToScan, $this->directoryToScan]);
    }

    /**
     * @dataProvider invalidDirectoriesProvider
     * @param array $directories
     */
    public function testInvalidDirectory(array $directories)
    {
        $this->expectException(InvalidDirectory::class);
        new DirectorySourceLocator($directories);
    }

    public function invalidDirectoriesProvider()
    {
        return [
            [[$this->directoryToScan, substr($this->directoryToScan, 0, strlen($this->directoryToScan)-1)]],
            [[$this->directoryToScan, 1]],
            [[$this->directoryToScan, true]],
            [[$this->directoryToScan, new \stdClass()]],
            [[$this->directoryToScan, null]],
        ];
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
