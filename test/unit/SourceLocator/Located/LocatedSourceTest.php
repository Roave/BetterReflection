<?php

namespace BetterReflectionTest\SourceLocator\Located;

use BetterReflection\SourceLocator\Exception\InvalidFileLocation;
use BetterReflection\SourceLocator\Located\LocatedSource;
use InvalidArgumentException;

/**
 * @covers \BetterReflection\SourceLocator\Located\LocatedSource
 */
class LocatedSourceTest extends \PHPUnit_Framework_TestCase
{
    public function testValuesHappyPath()
    {
        $source = '<?php echo "Hello world";';
        $file = __DIR__ . '/../../Fixture/NoNamespace.php';
        $locatedSource = new LocatedSource($source, $file);

        $this->assertSame($source, $locatedSource->getSource());
        $this->assertSame($file, $locatedSource->getFileName());
        $this->assertFalse($locatedSource->isEvaled());
        $this->assertFalse($locatedSource->isInternal());
    }

    public function testValuesWithNullFilename()
    {
        $source = '<?php echo "Hello world";';
        $file = null;
        $locatedSource = new LocatedSource($source, $file);

        $this->assertSame($source, $locatedSource->getSource());
        $this->assertNull($locatedSource->getFileName());
        $this->assertFalse($locatedSource->isEvaled());
        $this->assertFalse($locatedSource->isInternal());
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
        $this->expectException($expectedException);
        $this->expectExceptionMessage($expectedMessage);
        new LocatedSource($source, $file);
    }

    public function testConstructorThrowsExceptionIfEmptyFileGiven()
    {
        $this->expectException(InvalidFileLocation::class);
        $this->expectExceptionMessage('Filename was empty');
        new LocatedSource('<?php', '');
    }

    public function testConstructorThrowsExceptionIfFileDoesNotExist()
    {
        $this->expectException(InvalidFileLocation::class);
        $this->expectExceptionMessage('File does not exist');
        new LocatedSource('<?php', 'sdklfjdfslsdfhlkjsdglkjsdflgkj');
    }

    public function testConstructorThrowsExceptionIfFileIsNotAFile()
    {
        $this->expectException(InvalidFileLocation::class);
        $this->expectExceptionMessage('Is not a file');
        new LocatedSource('<?php', __DIR__);
    }

    public function testConstructorThrowsExceptionIfFileIsNotReadable()
    {
        $file = __DIR__ . '/../../Fixture/NoNamespace.php';

        $originalPermission = fileperms($file);
        chmod($file, 0000);

        $this->expectException(InvalidFileLocation::class);
        $this->expectExceptionMessage('File is not readable');

        try {
            new LocatedSource('<?php', $file);
        } catch (\Exception $e) {
            throw $e;
        } finally {
            chmod($file, $originalPermission);
        }
    }
}
