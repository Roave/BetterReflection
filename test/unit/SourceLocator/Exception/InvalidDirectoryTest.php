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

    public function testExceptionMessage()
    {
        $e = InvalidDirectory::fromNonDirectory('testDir');
        $this->assertEquals(sprintf('%s does not exists', 'testDir'), $e->getMessage());

        $e = InvalidDirectory::fromNonDirectory(__FILE__);
        $this->assertEquals(sprintf('%s is must to be a directory not a file', __FILE__), $e->getMessage());

        $e = InvalidDirectory::fromNonStringValue(new \stdClass());
        $expected = 'Expected string, stdClass given';
        $this->assertEquals($expected, $e->getMessage());

        $e = InvalidDirectory::fromNonStringValue(true);
        $expected = 'Expected string, boolean given';
        $this->assertEquals($expected, $e->getMessage());

        $e = InvalidDirectory::fromNonStringValue(null);
        $expected = 'Expected string, NULL given';
        $this->assertEquals($expected, $e->getMessage());

        $e = InvalidDirectory::fromNonStringValue(100);
        $expected = 'Expected string, integer given';
        $this->assertEquals($expected, $e->getMessage());

        $e = InvalidDirectory::fromNonStringValue(100.35);
        $expected = 'Expected string, double given';
        $this->assertEquals($expected, $e->getMessage());

        $e = InvalidDirectory::fromNonStringValue([100, 200]);
        $expected = 'Expected string, array given';
        $this->assertEquals($expected, $e->getMessage());
    }
}
