<?php
declare(strict_types=1);

namespace Roave\BetterReflectionTest\SourceLocator\Reflection;

use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\SourceLocator\Reflection\SourceStubber;
use Roave\BetterReflectionTest\Fixture\EmptyTrait;
use Traversable;
use Zend\Code\Reflection\ClassReflection;

/**
 * @covers \Roave\BetterReflection\SourceLocator\Reflection\SourceStubber
 */
class SourceStubberTest extends TestCase
{
    /**
     * @var SourceStubber
     */
    private $stubber;

    /**
     * {@inheritDoc}
     */
    protected function setUp() : void
    {
        $this->stubber = new SourceStubber();
    }

    public function testCanStubClass() : void
    {
        self::assertStringMatchesFormat(
            '%Aclass stdClass%A{%A}%A',
            $this->stubber->__invoke(new ClassReflection('stdClass'))
        );
    }

    public function testCanStubInterface() : void
    {
        self::assertStringMatchesFormat(
            '%Ainterface Traversable%A{%A}%A',
            $this->stubber->__invoke(new ClassReflection(Traversable::class))
        );
    }

    public function testCanStubTraits() : void
    {
        self::assertStringMatchesFormat(
            '%Atrait EmptyTrait%A{%A}%A',
            $this->stubber->__invoke(new ClassReflection(EmptyTrait::class))
        );
    }
}
