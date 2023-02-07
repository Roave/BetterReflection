<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\SourceLocator\Located;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\SourceLocator\Located\InternalLocatedSource;

#[CoversClass(InternalLocatedSource::class)]
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
