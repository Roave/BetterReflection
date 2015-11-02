<?php

namespace BetterReflectionTest\SourceLocator\Located;

use BetterReflection\SourceLocator\Located\EvaledLocatedSource;

/**
 * @covers \BetterReflection\SourceLocator\Located\EvaledLocatedSource
 */
class EvaledLocatedSourceTest extends \PHPUnit_Framework_TestCase
{
    public function testInternalsLocatedSource()
    {
        $locatedSource = new EvaledLocatedSource('foo');

        $this->assertSame('foo', $locatedSource->getSource());
        $this->assertNull($locatedSource->getFileName());
        $this->assertFalse($locatedSource->isInternal());
        $this->assertTrue($locatedSource->isEvaled());
    }
}
