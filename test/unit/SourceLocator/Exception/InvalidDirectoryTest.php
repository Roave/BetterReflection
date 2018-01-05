<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\SourceLocator\Exception;

use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\SourceLocator\Exception\InvalidDirectory;
use stdClass;

/**
 * @covers \Roave\BetterReflection\SourceLocator\Exception\InvalidDirectory
 */
class InvalidDirectoryTest extends TestCase
{
    /**
     * @dataProvider nonStringValuesProvider
     *
     * @param mixed $value
     */
    public function testFromNonStringValue(string $expectedMessage, $value) : void
    {
        $exception = InvalidDirectory::fromNonStringValue($value);

        self::assertInstanceOf(InvalidDirectory::class, $exception);
        self::assertSame($expectedMessage, $exception->getMessage());
    }

    /**
     * @return string[][]|mixed[][]
     */
    public function nonStringValuesProvider() : array
    {
        return [
            ['Expected string, stdClass given', new stdClass()],
            ['Expected string, boolean given', true],
            ['Expected string, NULL given', null],
            ['Expected string, integer given', 100],
            ['Expected string, double given', 100.35],
            ['Expected string, array given', []],
        ];
    }

    public function testFromNonDirectoryWithNonExistingPath() : void
    {
        $directory = \uniqid(\sys_get_temp_dir() . 'non-existing', true);
        $exception = InvalidDirectory::fromNonDirectory($directory);

        self::assertInstanceOf(InvalidDirectory::class, $exception);
        self::assertSame(\sprintf('"%s" does not exist', $directory), $exception->getMessage());
    }

    public function testFromNonDirectoryWithFile() : void
    {
        $exception = InvalidDirectory::fromNonDirectory(__FILE__);

        self::assertInstanceOf(InvalidDirectory::class, $exception);
        self::assertSame(\sprintf('"%s" must be a directory, not a file', __FILE__), $exception->getMessage());
    }
}
