<?php

namespace BetterReflectionTest\SourceLocator;

use BetterReflection\SourceLocator\Exception\InvalidFileLocation;
use BetterReflection\SourceLocator\LocatedSource;
use InvalidArgumentException;

/**
 * @covers \BetterReflection\SourceLocator\LocatedSource
 */
class LocatedSourceTest extends \PHPUnit_Framework_TestCase
{
    public function testValuesHappyPath()
    {
        $source = '<?php echo "Hello world";';
        $file = __DIR__ . '/../Fixture/NoNamespace.php';
        $locatedSource = new LocatedSource($source, $file);

        $this->assertSame($source, $locatedSource->getSource());
        $this->assertSame($file, $locatedSource->getFileName());
    }

    public function testValuesWithNullFilename()
    {
        $source = '<?php echo "Hello world";';
        $file = null;
        $locatedSource = new LocatedSource($source, $file);

        $this->assertSame($source, $locatedSource->getSource());
        $this->assertNull($locatedSource->getFileName());
    }

    /**
     * @return array
     */
    public function exceptionCasesProvider()
    {
        return [
            ['', null, InvalidArgumentException::class, 'Source code must be a non-empty string'],
            [123, null, InvalidArgumentException::class, 'Source code must be a non-empty string'],
            ['foo', 123, InvalidArgumentException::class, 'Filename must be a string or null'],
        ];
    }

    /**
     * @param string $source
     * @param string $file
     * @param string $expectedException
     * @param string $expectedMessage
     * @dataProvider exceptionCasesProvider
     */
    public function testThrowsExceptionWhenInvalidValuesGiven($source, $file, $expectedException, $expectedMessage)
    {
        $this->setExpectedException($expectedException, $expectedMessage);
        new LocatedSource($source, $file);
    }

    public function testConstructorThrowsExceptionIfEmptyFileGiven()
    {
        $this->setExpectedException(InvalidFileLocation::class, 'Filename was empty');
        new LocatedSource('<?php', '');
    }

    public function testConstructorThrowsExceptionIfFileDoesNotExist()
    {
        $this->setExpectedException(InvalidFileLocation::class, 'File does not exist');
        new LocatedSource('<?php', 'sdklfjdfslsdfhlkjsdglkjsdflgkj');
    }

    public function testConstructorThrowsExceptionIfFileIsNotAFile()
    {
        $this->setExpectedException(InvalidFileLocation::class, 'Is not a file');
        new LocatedSource('<?php', __DIR__);
    }
}
