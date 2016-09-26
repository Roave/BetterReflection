<?php

namespace BetterReflectionTest\SourceLocator\Exception;

use BetterReflection\SourceLocator\Exception\InvalidFileInfo;

/**
 * @covers \BetterReflection\SourceLocator\Exception\InvalidFileInfo
 */
class InvalidFileInfoTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider nonSplFileInfoProvider
     *
     * @param string $expectedMessage
     * @param mixed  $value
     *
     * @return void
     */
    public function testFromNonSplFileInfo($expectedMessage, $value)
    {
        $exception = InvalidFileInfo::fromNonSplFileInfo($value);

        self::assertInstanceOf(InvalidFileInfo::class, $exception);
        self::assertSame($expectedMessage, $exception->getMessage());
    }

    /**
     * @return string[][]|mixed[][]
     */
    public function nonSplFileInfoProvider()
    {
        return [
            ['Expected an iterator of SplFileInfo instances, stdClass given instead', new \stdClass()],
            ['Expected an iterator of SplFileInfo instances, boolean given instead', true],
            ['Expected an iterator of SplFileInfo instances, NULL given instead', null],
            ['Expected an iterator of SplFileInfo instances, integer given instead', 100],
            ['Expected an iterator of SplFileInfo instances, double given instead', 100.35],
            ['Expected an iterator of SplFileInfo instances, array given instead', []],
        ];
    }
}
