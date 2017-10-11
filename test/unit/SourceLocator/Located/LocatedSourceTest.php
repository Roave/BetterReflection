<?php
declare(strict_types=1);

namespace Rector\BetterReflectionTest\SourceLocator\Located;

use PHPUnit\Framework\TestCase;
use Rector\BetterReflection\SourceLocator\Located\LocatedSource;
use Rector\BetterReflection\Util\FileHelper;

/**
 * @covers \Rector\BetterReflection\SourceLocator\Located\LocatedSource
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
        self::assertNull($locatedSource->getExtensionName());
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
        self::assertNull($locatedSource->getExtensionName());
    }

    public function testEmptyStringSourceAllowed() : void
    {
        $source        = '';
        $file          = null;
        $locatedSource = new LocatedSource($source, $file);
        self::assertSame('', $locatedSource->getSource());
    }
}
