<?php

namespace Roave\BetterReflectionTest\SourceLocator\Located;

use Roave\BetterReflection\SourceLocator\Located\InternalLocatedSource;

/**
 * @covers \Roave\BetterReflection\SourceLocator\Located\InternalLocatedSource
 */
class InternalLocatedSourceTest extends \PHPUnit_Framework_TestCase
{
    public function testInternalsLocatedSource() : void
    {
        $locatedSource = new InternalLocatedSource('foo');

        self::assertSame('foo', $locatedSource->getSource());
        self::assertNull($locatedSource->getFileName());
        self::assertTrue($locatedSource->isInternal());
        self::assertFalse($locatedSource->isEvaled());
    }
}
