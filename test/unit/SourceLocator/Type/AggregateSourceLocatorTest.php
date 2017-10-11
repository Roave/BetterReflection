<?php
declare(strict_types=1);

namespace Rector\BetterReflectionTest\SourceLocator\Type;

use PHPUnit\Framework\TestCase;
use Rector\BetterReflection\Identifier\Identifier;
use Rector\BetterReflection\Identifier\IdentifierType;
use Rector\BetterReflection\Reflection\ReflectionClass;
use Rector\BetterReflection\Reflector\Reflector;
use Rector\BetterReflection\SourceLocator\Ast\Locator;
use Rector\BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use Rector\BetterReflection\SourceLocator\Type\SourceLocator;
use Rector\BetterReflection\SourceLocator\Type\StringSourceLocator;
use Rector\BetterReflectionTest\BetterReflectionSingleton;

/**
 * @covers \Rector\BetterReflection\SourceLocator\Type\AggregateSourceLocator
 */
class AggregateSourceLocatorTest extends TestCase
{
    /**
     * @var Locator
     */
    private $astLocator;

    protected function setUp() : void
    {
        parent::setUp();

        $this->astLocator = BetterReflectionSingleton::instance()->astLocator();
    }

    /**
     * @return Reflector|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getMockReflector()
    {
        return $this->createMock(Reflector::class);
    }

    public function testInvokeWillTraverseAllGivenLocatorsAndFailToResolve() : void
    {
        $locator1   = $this->createMock(SourceLocator::class);
        $locator2   = $this->createMock(SourceLocator::class);
        $identifier = new Identifier('Foo', new IdentifierType(IdentifierType::IDENTIFIER_CLASS));

        $locator1->expects($this->once())->method('locateIdentifier');
        $locator2->expects($this->once())->method('locateIdentifier');

        self::assertNull((new AggregateSourceLocator([$locator1, $locator2]))->locateIdentifier($this->getMockReflector(), $identifier));
    }

    public function testInvokeWillTraverseAllGivenLocatorsAndSucceed() : void
    {
        $identifier = new Identifier('Foo', new IdentifierType(IdentifierType::IDENTIFIER_CLASS));

        $locator1 = $this->createMock(SourceLocator::class);
        $locator2 = $this->createMock(SourceLocator::class);
        $locator3 = $this->createMock(SourceLocator::class);
        $locator4 = $this->createMock(SourceLocator::class);

        $source3 = $this->createMock(ReflectionClass::class);

        $locator1->expects($this->once())->method('locateIdentifier');
        $locator2->expects($this->once())->method('locateIdentifier');
        $locator3->expects($this->once())->method('locateIdentifier')->willReturn($source3);
        $locator4->expects($this->never())->method('locateIdentifier');

        self::assertSame(
            $source3,
            (new AggregateSourceLocator([
                $locator1,
                $locator2,
                $locator3,
                $locator4,
            ]))->locateIdentifier($this->getMockReflector(), $identifier)
        );
    }

    public function testWillNotResolveWithEmptyLocatorsList() : void
    {
        self::assertNull(
            (new AggregateSourceLocator([]))->locateIdentifier(
                $this->getMockReflector(),
                new Identifier('Foo', new IdentifierType(IdentifierType::IDENTIFIER_CLASS))
            )
        );
    }

    public function testTwoStringSourceLocatorsResolveCorrectly() : void
    {
        $identifier = new Identifier('Foo', new IdentifierType(IdentifierType::IDENTIFIER_CLASS));

        $locator1 = new StringSourceLocator('<?php', $this->astLocator);
        $locator2 = new StringSourceLocator('<?php class Foo {}', $this->astLocator);

        $aggregate = new AggregateSourceLocator([$locator1, $locator2]);

        $reflection = $aggregate->locateIdentifier($this->getMockReflector(), $identifier);

        self::assertSame('Foo', $reflection->getName());
    }

    public function testLocateIdentifiersByTypeAggregatesSource() : void
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

        self::assertSame(
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
