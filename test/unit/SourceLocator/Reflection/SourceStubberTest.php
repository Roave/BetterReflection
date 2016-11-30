<?php

namespace Roave\BetterReflectionTest\SourceLocator\Reflection;

use Roave\BetterReflection\SourceLocator\Reflection\SourceStubber;
use Roave\BetterReflectionTest\Fixture\EmptyTrait;
use Zend\Code\Reflection\ClassReflection;

/**
 * @covers \Roave\BetterReflection\SourceLocator\Reflection\SourceStubber
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

    public function testCanStubInterface()
    {
        $this->assertStringMatchesFormat(
            '%Ainterface Traversable%A{%A}%A',
            $this->stubber->__invoke(new ClassReflection(\Traversable::class))
        );
    }

    public function testCanStubTraits()
    {
        $this->assertStringMatchesFormat(
            '%Atrait EmptyTrait%A{%A}%A',
            $this->stubber->__invoke(new ClassReflection(EmptyTrait::class))
        );
    }
}
