<?php

namespace BetterReflectionTest\SourceLocator;

use BetterReflection\SourceLocator\InternalLocatedSource;

/**
 * @covers \BetterReflection\SourceLocator\InternalLocatedSource
 */
class InternalLocatedSourceTest extends \PHPUnit_Framework_TestCase
{
    public function testInternalsLocatedSource()
    {
        $locatedSource = new InternalLocatedSource('foo');

        $this->assertSame('foo', $locatedSource->getSource());
        $this->assertNull($locatedSource->getFileName());
    }
}
