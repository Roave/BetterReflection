<?php

namespace BetterReflectionTest\SourceLocator\Type;

use BetterReflection\Identifier\Identifier;
use BetterReflection\Identifier\IdentifierType;
use BetterReflection\Reflection\ReflectionClass;
use BetterReflection\Reflector\Reflector;
use BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use BetterReflection\SourceLocator\Type\SourceLocator;
use BetterReflection\SourceLocator\Type\StringSourceLocator;

/**
 * @covers \BetterReflection\SourceLocator\Type\AggregateSourceLocator
 */
class AggregateSourceLocatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return Reflector|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getMockReflector()
    {
        return $this->getMock(Reflector::class);
    }

    public function testInvokeWillTraverseAllGivenLocatorsAndFailToResolve()
    {
        $locator1   = $this->getMock(SourceLocator::class);
        $locator2   = $this->getMock(SourceLocator::class);
        $identifier = new Identifier('Foo', new IdentifierType(IdentifierType::IDENTIFIER_CLASS));

        $locator1->expects($this->once())->method('locateIdentifier');
        $locator2->expects($this->once())->method('locateIdentifier');

        $this->assertNull((new AggregateSourceLocator([$locator1, $locator2]))->locateIdentifier($this->getMockReflector(), $identifier));
    }

    public function testInvokeWillTraverseAllGivenLocatorsAndSucceed()
    {
        $identifier = new Identifier('Foo', new IdentifierType(IdentifierType::IDENTIFIER_CLASS));

        $locator1   = $this->getMock(SourceLocator::class);
        $locator2   = $this->getMock(SourceLocator::class);
        $locator3   = $this->getMock(SourceLocator::class);
        $locator4   = $this->getMock(SourceLocator::class);

        $source3     = $this->getMockBuilder(ReflectionClass::class)
            ->disableOriginalConstructor()
            ->getMock();

        $locator1->expects($this->once())->method('locateIdentifier');
        $locator2->expects($this->once())->method('locateIdentifier');
        $locator3->expects($this->once())->method('locateIdentifier')->willReturn($source3);
        $locator4->expects($this->never())->method('locateIdentifier');

        $this->assertSame(
            $source3,
            (new AggregateSourceLocator([
                $locator1,
                $locator2,
                $locator3,
                $locator4,
            ]))->locateIdentifier($this->getMockReflector(), $identifier)
        );
    }

    public function testWillNotResolveWithEmptyLocatorsList()
    {
        $this->assertNull(
            (new AggregateSourceLocator([]))->locateIdentifier(
                $this->getMockReflector(),
                new Identifier('Foo', new IdentifierType(IdentifierType::IDENTIFIER_CLASS))
            )
        );
    }

    public function testTwoStringSourceLocatorsResolveCorrectly()
    {
        $identifier = new Identifier('Foo', new IdentifierType(IdentifierType::IDENTIFIER_CLASS));

        $locator1 = new StringSourceLocator('<?php');
        $locator2 = new StringSourceLocator('<?php class Foo {}');

        $aggregate = new AggregateSourceLocator([$locator1, $locator2]);

        $reflection = $aggregate->locateIdentifier($this->getMockReflector(), $identifier);

        $this->assertSame('Foo', $reflection->getName());
    }

    public function testLocateIdentifiersByTypeAggregatesSource()
    {
        $identifierType = new IdentifierType;

        $locator1   = $this->getMock(SourceLocator::class);
        $locator2   = $this->getMock(SourceLocator::class);
        $locator3   = $this->getMock(SourceLocator::class);
        $locator4   = $this->getMock(SourceLocator::class);

        $source2     = $this->getMockBuilder(ReflectionClass::class)
            ->disableOriginalConstructor()
            ->getMock();

        $source3     = $this->getMockBuilder(ReflectionClass::class)
            ->disableOriginalConstructor()
            ->getMock();

        $locator1->expects($this->once())->method('locateIdentifiersByType')->willReturn([]);
        $locator2->expects($this->once())->method('locateIdentifiersByType')->willReturn([$source2]);
        $locator3->expects($this->once())->method('locateIdentifiersByType')->willReturn([$source3]);
        $locator4->expects($this->once())->method('locateIdentifiersByType')->willReturn([]);

        $this->assertSame(
            [$source2, $source3],
            (new AggregateSourceLocator([
                $locator1,
                $locator2,
                $locator3,
                $locator4,
            ]))->locateIdentifiersByType($this->getMockReflector(), $identifierType)
        );
    }
}
