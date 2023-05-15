<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\SourceLocator\Located;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\SourceLocator\Located\LocatedSource;
use Roave\BetterReflection\Util\FileHelper;

#[CoversClass(LocatedSource::class)]
class LocatedSourceTest extends TestCase
{
    public function testValuesHappyPath(): void
    {
        $source        = '<?php echo "Hello world";';
        $file          = FileHelper::normalizeWindowsPath(__DIR__ . '/../../Fixture/NoNamespace.php');
        $locatedSource = new LocatedSource($source, 'name', $file);

        self::assertSame($source, $locatedSource->getSource());
        self::assertSame('name', $locatedSource->getName());
        self::assertSame($file, $locatedSource->getFileName());
        self::assertFalse($locatedSource->isEvaled());
        self::assertFalse($locatedSource->isInternal());
        self::assertNull($locatedSource->getExtensionName());
        self::assertNull($locatedSource->getAliasName());
    }

    public function testValuesWithNullFilename(): void
    {
        $source        = '<?php echo "Hello world";';
        $file          = null;
        $locatedSource = new LocatedSource($source, 'name', $file);

        self::assertSame($source, $locatedSource->getSource());
        self::assertSame('name', $locatedSource->getName());
        self::assertNull($locatedSource->getFileName());
        self::assertFalse($locatedSource->isEvaled());
        self::assertFalse($locatedSource->isInternal());
        self::assertNull($locatedSource->getExtensionName());
        self::assertNull($locatedSource->getAliasName());
    }

    public function testEmptyStringSourceAllowed(): void
    {
        $source        = '';
        $file          = null;
        $locatedSource = new LocatedSource($source, 'name', $file);
        self::assertSame('', $locatedSource->getSource());
    }
}
