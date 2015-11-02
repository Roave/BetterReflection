<?php

namespace BetterReflectionTest\SourceLocator\Located;

use BetterReflection\SourceLocator\Located\InternalLocatedSource;

/**
 * @covers \BetterReflection\SourceLocator\Located\InternalLocatedSource
 */
class InternalLocatedSourceTest extends \PHPUnit_Framework_TestCase
{
    public function testInternalsLocatedSource()
    {
        $locatedSource = new InternalLocatedSource('foo');

        $this->assertSame('foo', $locatedSource->getSource());
        $this->assertNull($locatedSource->getFileName());
        $this->assertTrue($locatedSource->isInternal());
        $this->assertFalse($locatedSource->isEvaled());
    }
}
