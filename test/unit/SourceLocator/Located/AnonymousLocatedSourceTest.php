<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\SourceLocator\Located;

use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\SourceLocator\Located\AnonymousLocatedSource;
use Roave\BetterReflection\Util\FileHelper;

/**
 * @covers \Roave\BetterReflection\SourceLocator\Located\AnonymousLocatedSource
 */
class AnonymousLocatedSourceTest extends TestCase
{
    public function testInternalsLocatedSource(): void
    {
        $file          = FileHelper::normalizeWindowsPath(__DIR__ . '/../../Fixture/NoNamespace.php');
        $locatedSource = new AnonymousLocatedSource('foo', $file);

        self::assertSame('foo', $locatedSource->getSource());
        self::assertNull($locatedSource->getName());
        self::assertSame($file, $locatedSource->getFileName());
    }
}
