<?php
declare(strict_types=1);

namespace Rector\BetterReflectionTest\SourceLocator\Exception;

use PHPUnit\Framework\TestCase;
use Rector\BetterReflection\SourceLocator\Exception\InvalidFileInfo;
use stdClass;

/**
 * @covers \Rector\BetterReflection\SourceLocator\Exception\InvalidFileInfo
 */
class InvalidFileInfoTest extends TestCase
{
    /**
     * @dataProvider nonSplFileInfoProvider
     *
     * @param string $expectedMessage
     * @param mixed  $value
     *
     * @return void
     */
    public function testFromNonSplFileInfo(string $expectedMessage, $value) : void
    {
        $exception = InvalidFileInfo::fromNonSplFileInfo($value);

        self::assertInstanceOf(InvalidFileInfo::class, $exception);
        self::assertSame($expectedMessage, $exception->getMessage());
    }

    /**
     * @return string[][]|mixed[][]
     */
    public function nonSplFileInfoProvider() : array
    {
        return [
            ['Expected an iterator of SplFileInfo instances, stdClass given instead', new stdClass()],
            ['Expected an iterator of SplFileInfo instances, boolean given instead', true],
            ['Expected an iterator of SplFileInfo instances, NULL given instead', null],
            ['Expected an iterator of SplFileInfo instances, integer given instead', 100],
            ['Expected an iterator of SplFileInfo instances, double given instead', 100.35],
            ['Expected an iterator of SplFileInfo instances, array given instead', []],
        ];
    }
}
