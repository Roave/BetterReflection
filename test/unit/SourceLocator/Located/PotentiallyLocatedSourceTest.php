<?php

namespace BetterReflectionTest\SourceLocator\Located;

use BetterReflection\SourceLocator\Exception\InvalidFileLocation;
use BetterReflection\SourceLocator\Located\PotentiallyLocatedSource;
use InvalidArgumentException;

/**
 * @covers \BetterReflection\SourceLocator\Located\PotentiallyLocatedSource
 */
class LocatedSourceTest extends \PHPUnit_Framework_TestCase
{
    public function testValuesHappyPath()
    {
        $source = '<?php echo "Hello world";';
        $file = __DIR__ . '/../../Fixture/NoNamespace.php';
        $locatedSource = new PotentiallyLocatedSource($source, $file);

        $this->assertSame($source, $locatedSource->getSource());
        $this->assertSame($file, $locatedSource->getFileName());
        $this->assertFalse($locatedSource->isEvaled());
        $this->assertFalse($locatedSource->isInternal());
    }

    public function testValuesWithNullFilename()
    {
        $source = '<?php echo "Hello world";';
        $file = null;
        $locatedSource = new PotentiallyLocatedSource($source, $file);

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
        $this->setExpectedException($expectedException, $expectedMessage);
        new PotentiallyLocatedSource($source, $file);
    }

    public function testConstructorThrowsExceptionIfEmptyFileGiven()
    {
        $this->setExpectedException(InvalidFileLocation::class, 'Filename was empty');
        new PotentiallyLocatedSource('<?php', '');
    }

    public function testConstructorThrowsExceptionIfFileDoesNotExist()
    {
        $this->setExpectedException(InvalidFileLocation::class, 'File does not exist');
        new PotentiallyLocatedSource('<?php', 'sdklfjdfslsdfhlkjsdglkjsdflgkj');
    }

    public function testConstructorThrowsExceptionIfFileIsNotAFile()
    {
        $this->setExpectedException(InvalidFileLocation::class, 'Is not a file');
        new PotentiallyLocatedSource('<?php', __DIR__);
    }

    public function testConstructorThrowsExceptionIfFileIsNotReadable()
    {
        $file = __DIR__ . '/../../Fixture/NoNamespace.php';

        $originalPermission = fileperms($file);
        chmod($file, 0000);

        $this->setExpectedException(InvalidFileLocation::class, 'File is not readable');

        try {
            new PotentiallyLocatedSource('<?php', $file);
        } catch (\Exception $e) {
            throw $e;
        } finally {
            chmod($file, $originalPermission);
        }
    }
}
