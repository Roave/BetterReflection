<?php

namespace BetterReflectionTest\SourceLocator\Type;

use BetterReflection\SourceLocator\Exception\InvalidDirectory;
use BetterReflection\SourceLocator\Type\MultipleDirectoriesAggregateSourceLocator;

/**
 * @covers \BetterReflection\SourceLocator\Type\MultipleDirectoriesAggregateSourceLocator
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
        new MultipleDirectoriesAggregateSourceLocator([$this->directoryToScan, $this->directoryToScan]);
    }

    /**
     * @dataProvider invalidDirectoriesProvider
     * @param array $directories
     */
    public function testInvalidDirectory(array $directories)
    {
        $this->expectException(InvalidDirectory::class);
        new MultipleDirectoriesAggregateSourceLocator($directories);
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
}
