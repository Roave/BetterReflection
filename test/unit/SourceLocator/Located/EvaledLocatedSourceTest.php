<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\SourceLocator\Located;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\SourceLocator\Located\EvaledLocatedSource;

#[CoversClass(EvaledLocatedSource::class)]
class EvaledLocatedSourceTest extends TestCase
{
    public function testInternalsLocatedSource(): void
    {
        $locatedSource = new EvaledLocatedSource('foo', 'name');

        self::assertSame('foo', $locatedSource->getSource());
        self::assertSame('name', $locatedSource->getName());
        self::assertNull($locatedSource->getFileName());
        self::assertFalse($locatedSource->isInternal());
        self::assertTrue($locatedSource->isEvaled());
    }
}
