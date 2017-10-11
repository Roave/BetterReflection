<?php
declare(strict_types=1);

namespace Rector\BetterReflectionTest\SourceLocator\Located;

use PHPUnit\Framework\TestCase;
use Rector\BetterReflection\SourceLocator\Located\EvaledLocatedSource;

/**
 * @covers \Rector\BetterReflection\SourceLocator\Located\EvaledLocatedSource
 */
class EvaledLocatedSourceTest extends TestCase
{
    public function testInternalsLocatedSource() : void
    {
        $locatedSource = new EvaledLocatedSource('foo');

        self::assertSame('foo', $locatedSource->getSource());
        self::assertNull($locatedSource->getFileName());
        self::assertFalse($locatedSource->isInternal());
        self::assertTrue($locatedSource->isEvaled());
    }
}
