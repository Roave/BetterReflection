<?php

namespace BetterReflectionTest\SourceLocator;
use BetterReflection\Identifier\Identifier;
use BetterReflection\Identifier\IdentifierType;
use BetterReflection\SourceLocator\AggregateSourceLocator;
use BetterReflection\SourceLocator\LocatedSource;
use BetterReflection\SourceLocator\SourceLocator;
use BetterReflection\SourceLocator\StringSourceLocator;

/**
 * @covers \BetterReflection\SourceLocator\AggregateSourceLocator
 */
class AggregateSourceLocatorTest extends \PHPUnit_Framework_TestCase
{
    public function testInvokeWillTraverseAllGivenLocatorsAndFailToResolve()
    {
        $locator1   = $this->getMock(SourceLocator::class);
        $locator2   = $this->getMock(SourceLocator::class);
        $identifier = new Identifier('Foo', new IdentifierType(IdentifierType::IDENTIFIER_CLASS));

        $locator1->expects($this->once())->method('__invoke')->with($identifier);
        $locator2->expects($this->once())->method('__invoke')->with($identifier);

        $this->assertNull((new AggregateSourceLocator([$locator1, $locator2]))->__invoke($identifier));
    }

    public function testInvokeWillTraverseAllGivenLocatorsAndSucceed()
    {
        $locator1   = $this->getMock(SourceLocator::class);
        $locator2   = $this->getMock(SourceLocator::class);
        $locator3   = $this->getMock(SourceLocator::class);
        $identifier = new Identifier('Foo', new IdentifierType(IdentifierType::IDENTIFIER_CLASS));
        $source     = new LocatedSource('<?php foo', null);

        $locator1->expects($this->once())->method('__invoke')->with($identifier);
        $locator2->expects($this->once())->method('__invoke')->with($identifier)->willReturn($source);
        $locator3->expects($this->never())->method('__invoke');

        $this->assertSame($source, (new AggregateSourceLocator([$locator1, $locator2]))->__invoke($identifier));
    }

    public function testWillNotResolveWithEmptyLocatorsList()
    {
        $this->assertNull(
            (new AggregateSourceLocator([]))
                ->__invoke(new Identifier('Foo', new IdentifierType(IdentifierType::IDENTIFIER_CLASS)))
        );
    }
}
