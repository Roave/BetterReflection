<?php

namespace BetterReflectionTest\SourceLocator\Exception;

use BetterReflection\SourceLocator\Exception\InvalidFileInfo;

/**
 * @covers \BetterReflection\SourceLocator\Exception\InvalidFileInfo
 */
class InvalidFileInfoTest extends \PHPUnit_Framework_TestCase
{
    public function testExceptionMessage()
    {
        $e = InvalidFileInfo::fromNonSplFileInfo(new \stdClass());
        $expected = 'Expected an iterator of SplFileInfo instances, stdClass given instead';
        $this->assertEquals($expected, $e->getMessage());

        $e = InvalidFileInfo::fromNonSplFileInfo(true);
        $expected = 'Expected an iterator of SplFileInfo instances, boolean given instead';
        $this->assertEquals($expected, $e->getMessage());

        $e = InvalidFileInfo::fromNonSplFileInfo(null);
        $expected = 'Expected an iterator of SplFileInfo instances, NULL given instead';
        $this->assertEquals($expected, $e->getMessage());

        $e = InvalidFileInfo::fromNonSplFileInfo(100);
        $expected = 'Expected an iterator of SplFileInfo instances, integer given instead';
        $this->assertEquals($expected, $e->getMessage());

        $e = InvalidFileInfo::fromNonSplFileInfo(100.35);
        $expected = 'Expected an iterator of SplFileInfo instances, double given instead';
        $this->assertEquals($expected, $e->getMessage());

        $e = InvalidFileInfo::fromNonSplFileInfo([100, 200]);
        $expected = 'Expected an iterator of SplFileInfo instances, array given instead';
        $this->assertEquals($expected, $e->getMessage());
    }
}
