<?php

namespace BetterReflectionTest\SourceLocator\Exception;

use BetterReflection\SourceLocator\Exception\InvalidDirectory;

/**
 * @covers \BetterReflection\SourceLocator\Exception\InvalidDirectory
 */
class InvalidDirectoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider nonStringValuesProvider
     *
     * @param string $expectedMessage
     * @param mixed $value
     *
     * @return void
     */
    public function testFromNonStringValue($expectedMessage, $value)
    {
        $exception = InvalidDirectory::fromNonStringValue($value);

        self::assertInstanceOf(InvalidDirectory::class, $exception);
        self::assertSame($expectedMessage, $exception->getMessage());
    }

    /**
     * @return string[][]|mixed[][]
     */
    public function nonStringValuesProvider()
    {
        return [
            ['Expected string, stdClass given', new \stdClass()],
            ['Expected string, boolean given', true],
            ['Expected string, NULL given', null],
            ['Expected string, integer given', 100],
            ['Expected string, double given', 100.35],
            ['Expected string, array given', []],
        ];
    }

    public function testFromNonDirectoryWithNonExistingPath()
    {
        $directory = uniqid(sys_get_temp_dir() . 'non-existing', true);
        $exception = InvalidDirectory::fromNonDirectory($directory);

        self::assertInstanceOf(InvalidDirectory::class, $exception);
        self::assertSame(sprintf('"%s" does not exists', $directory), $exception->getMessage());
    }

    public function testFromNonDirectoryWithFile()
    {
        $exception = InvalidDirectory::fromNonDirectory(__FILE__);

        self::assertInstanceOf(InvalidDirectory::class, $exception);
        self::assertSame(sprintf('"%s" must be a directory, not a file', __FILE__), $exception->getMessage());
    }
}
