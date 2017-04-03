<?php

namespace Roave\BetterReflectionTest\SourceLocator\Located;

use Roave\BetterReflection\SourceLocator\Exception\InvalidFileLocation;
use Roave\BetterReflection\SourceLocator\Located\LocatedSource;

/**
 * @covers \Roave\BetterReflection\SourceLocator\Located\LocatedSource
 */
class LocatedSourceTest extends \PHPUnit_Framework_TestCase
{
    public function testValuesHappyPath() : void
    {
        $source = '<?php echo "Hello world";';
        $file = __DIR__ . '/../../Fixture/NoNamespace.php';
        $locatedSource = new LocatedSource($source, $file);

        self::assertSame($source, $locatedSource->getSource());
        self::assertSame($file, $locatedSource->getFileName());
        self::assertFalse($locatedSource->isEvaled());
        self::assertFalse($locatedSource->isInternal());
    }

    public function testValuesWithNullFilename() : void
    {
        $source = '<?php echo "Hello world";';
        $file = null;
        $locatedSource = new LocatedSource($source, $file);

        self::assertSame($source, $locatedSource->getSource());
        self::assertNull($locatedSource->getFileName());
        self::assertFalse($locatedSource->isEvaled());
        self::assertFalse($locatedSource->isInternal());
    }

    public function testEmptyStringSourceAllowed() : void
    {
        $source = '';
        $file = null;
        $locatedSource = new LocatedSource($source, $file);
        self::assertSame('', $locatedSource->getSource());
    }

    public function testConstructorThrowsExceptionIfEmptyFileGiven() : void
    {
        $this->expectException(InvalidFileLocation::class);
        $this->expectExceptionMessage('Filename was empty');
        new LocatedSource('<?php', '');
    }

    public function testConstructorThrowsExceptionIfFileDoesNotExist() : void
    {
        $this->expectException(InvalidFileLocation::class);
        $this->expectExceptionMessage('File does not exist');
        new LocatedSource('<?php', 'sdklfjdfslsdfhlkjsdglkjsdflgkj');
    }

    public function testConstructorThrowsExceptionIfFileIsNotAFile() : void
    {
        $this->expectException(InvalidFileLocation::class);
        $this->expectExceptionMessage('Is not a file');
        new LocatedSource('<?php', __DIR__);
    }

    public function testConstructorThrowsExceptionIfFileIsNotReadable() : void
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
