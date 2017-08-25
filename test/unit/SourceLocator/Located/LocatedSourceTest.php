<?php
declare(strict_types=1);

namespace Roave\BetterReflectionTest\SourceLocator\Located;

use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\SourceLocator\Located\LocatedSource;
use Roave\BetterReflection\Util\FileHelper;

/**
 * @covers \Roave\BetterReflection\SourceLocator\Located\LocatedSource
 */
class LocatedSourceTest extends TestCase
{
    public function testValuesHappyPath() : void
    {
        $source        = '<?php echo "Hello world";';
        $file          = FileHelper::normalizeWindowsPath(__DIR__ . '/../../Fixture/NoNamespace.php');
        $locatedSource = new LocatedSource($source, $file);

        self::assertSame($source, $locatedSource->getSource());
        self::assertSame($file, $locatedSource->getFileName());
        self::assertFalse($locatedSource->isEvaled());
        self::assertFalse($locatedSource->isInternal());
    }

    public function testValuesWithNullFilename() : void
    {
        $source        = '<?php echo "Hello world";';
        $file          = null;
        $locatedSource = new LocatedSource($source, $file);

        self::assertSame($source, $locatedSource->getSource());
        self::assertNull($locatedSource->getFileName());
        self::assertFalse($locatedSource->isEvaled());
        self::assertFalse($locatedSource->isInternal());
    }

    public function testEmptyStringSourceAllowed() : void
    {
        $source        = '';
        $file          = null;
        $locatedSource = new LocatedSource($source, $file);
        self::assertSame('', $locatedSource->getSource());
    }
}
