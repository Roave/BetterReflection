<?php

namespace BetterReflectionTest\SourceLocator;

use BetterReflection\SourceLocator\SourceStubber;
use Zend\Code\Reflection\ClassReflection;

/**
 * @covers \BetterReflection\SourceLocator\SourceStubber
 */
class SourceStubberTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SourceStubber
     */
    private $stubber;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->stubber = new SourceStubber();
    }

    public function testCanStubClass()
    {
        $this->assertStringMatchesFormat(
            '%Aclass stdClass%A{%A}%A',
            $this->stubber->__invoke(new ClassReflection('stdClass'))
        );
    }
}
