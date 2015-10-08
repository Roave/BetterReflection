<?php

namespace BetterReflectionTest\SourceLocator\Type;

use BetterReflection\Identifier\Identifier;
use BetterReflection\Identifier\IdentifierType;
use BetterReflection\SourceLocator\Located\DefiniteLocatedSource;
use BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use BetterReflection\SourceLocator\Located\PotentiallyLocatedSource;
use BetterReflection\SourceLocator\Type\SourceLocator;
use BetterReflection\SourceLocator\Type\StringSourceLocator;

/**
 * @covers \BetterReflection\SourceLocator\Type\AggregateSourceLocator
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
        $identifier = new Identifier('Foo', new IdentifierType(IdentifierType::IDENTIFIER_CLASS));

        $locator1   = $this->getMock(SourceLocator::class);
        $locator2   = $this->getMock(SourceLocator::class);
        $locator3   = $this->getMock(SourceLocator::class);
        $locator4   = $this->getMock(SourceLocator::class);

        $source2     = new PotentiallyLocatedSource('<?php', null);
        $source3     = DefiniteLocatedSource::fromPotentiallyLocatedSource(new PotentiallyLocatedSource('<?php foo', null));

        $locator1->expects($this->once())->method('__invoke')->with($identifier);
        $locator2->expects($this->once())->method('__invoke')->with($identifier)->willReturn($source2);
        $locator3->expects($this->once())->method('__invoke')->with($identifier)->willReturn($source3);
        $locator4->expects($this->never())->method('__invoke');

        $this->assertSame($source3, (new AggregateSourceLocator([$locator1, $locator2, $locator3, $locator4]))->__invoke($identifier));
    }

    public function testWillNotResolveWithEmptyLocatorsList()
    {
        $this->assertNull(
            (new AggregateSourceLocator([]))
                ->__invoke(new Identifier('Foo', new IdentifierType(IdentifierType::IDENTIFIER_CLASS)))
        );
    }

    public function testTwoStringSourceLocatorsResolveCorrectly()
    {
        $identifier = new Identifier('Foo', new IdentifierType(IdentifierType::IDENTIFIER_CLASS));

        $locator1 = new StringSourceLocator('<?php');
        $locator2 = new StringSourceLocator('<?php class Foo {}');

        $aggregate = new AggregateSourceLocator([$locator1, $locator2]);
        $locatedSource = $aggregate->__invoke($identifier);

        $this->assertSame('<?php class Foo {}', $locatedSource->getSource());
    }
}
