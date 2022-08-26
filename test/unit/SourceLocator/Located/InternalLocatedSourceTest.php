<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\SourceLocator\Located;

use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\SourceLocator\Located\InternalLocatedSource;

/** @covers \Roave\BetterReflection\SourceLocator\Located\InternalLocatedSource */
class InternalLocatedSourceTest extends TestCase
{
    public function testInternalsLocatedSource(): void
    {
        $locatedSource = new InternalLocatedSource('foo', 'name', 'fooExt');

        self::assertSame('foo', $locatedSource->getSource());
        self::assertSame('name', $locatedSource->getName());
        self::assertNull($locatedSource->getFileName());
        self::assertTrue($locatedSource->isInternal());
        self::assertFalse($locatedSource->isEvaled());
        self::assertSame('fooExt', $locatedSource->getExtensionName());
    }
}
