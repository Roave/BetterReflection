<?php

namespace Roave\BetterReflectionTest\SourceLocator\Type;

use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\Identifier\IdentifierType;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\SourceLocator;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;

/**
 * @covers \Roave\BetterReflection\SourceLocator\Type\AggregateSourceLocator
 */
class AggregateSourceLocatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return Reflector|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getMockReflector()
    {
        return $this->createMock(Reflector::class);
    }

    public function testInvokeWillTraverseAllGivenLocatorsAndFailToResolve()
    {
        $locator1   = $this->createMock(SourceLocator::class);
        $locator2   = $this->createMock(SourceLocator::class);
        $identifier = new Identifier('Foo', new IdentifierType(IdentifierType::IDENTIFIER_CLASS));

        $locator1->expects($this->once())->method('locateIdentifier');
        $locator2->expects($this->once())->method('locateIdentifier');

        $this->assertNull((new AggregateSourceLocator([$locator1, $locator2]))->locateIdentifier($this->getMockReflector(), $identifier));
    }

    public function testInvokeWillTraverseAllGivenLocatorsAndSucceed()
    {
        $identifier = new Identifier('Foo', new IdentifierType(IdentifierType::IDENTIFIER_CLASS));

        $locator1   = $this->createMock(SourceLocator::class);
        $locator2   = $this->createMock(SourceLocator::class);
        $locator3   = $this->createMock(SourceLocator::class);
        $locator4   = $this->createMock(SourceLocator::class);

        $source3     = $this->createMock(ReflectionClass::class);

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

        $locator1 = $this->createMock(SourceLocator::class);
        $locator2 = $this->createMock(SourceLocator::class);
        $locator3 = $this->createMock(SourceLocator::class);
        $locator4 = $this->createMock(SourceLocator::class);

        $source2 = $this->createMock(ReflectionClass::class);

        $source3 = $this->createMock(ReflectionClass::class);

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
