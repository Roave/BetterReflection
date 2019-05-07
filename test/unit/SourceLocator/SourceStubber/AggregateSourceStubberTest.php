<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\SourceLocator\SourceStubber;

use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\SourceLocator\SourceStubber\AggregateSourceStubber;
use Roave\BetterReflection\SourceLocator\SourceStubber\SourceStubber;
use Roave\BetterReflection\SourceLocator\SourceStubber\StubData;

/**
 * @covers \Roave\BetterReflection\SourceLocator\SourceStubber\AggregateSourceStubber
 */
class AggregateSourceStubberTest extends TestCase
{
    public function testTraverseAllGivenSourceStubbersAndFailToGenerateClassStub() : void
    {
        $sourceStubber1 = $this->createMock(SourceStubber::class);
        $sourceStubber2 = $this->createMock(SourceStubber::class);

        $sourceStubber1->expects($this->once())->method('generateClassStub');
        $sourceStubber2->expects($this->once())->method('generateClassStub');

        self::assertNull((new AggregateSourceStubber($sourceStubber1, $sourceStubber2))->generateClassStub('SomeClass'));
    }

    public function testTraverseAllGivenSourceStubbersAndSucceedToGenerateClassStub() : void
    {
        $sourceStubber1 = $this->createMock(SourceStubber::class);
        $sourceStubber2 = $this->createMock(SourceStubber::class);
        $sourceStubber3 = $this->createMock(SourceStubber::class);
        $sourceStubber4 = $this->createMock(SourceStubber::class);

        $stubData = new StubData('<?php class SomeClass {}', null);

        $sourceStubber1->expects($this->once())->method('generateClassStub');
        $sourceStubber2->expects($this->once())->method('generateClassStub');
        $sourceStubber3->expects($this->once())->method('generateClassStub')->willReturn($stubData);
        $sourceStubber4->expects($this->never())->method('generateClassStub');

        self::assertSame(
            $stubData,
            (new AggregateSourceStubber(
                $sourceStubber1,
                $sourceStubber2,
                $sourceStubber3,
                $sourceStubber4
            ))->generateClassStub('SomeClass')
        );
    }

    public function testTraverseAllGivenSourceStubbersAndFailToGenerateFunctionStub() : void
    {
        $sourceStubber1 = $this->createMock(SourceStubber::class);
        $sourceStubber2 = $this->createMock(SourceStubber::class);

        $sourceStubber1->expects($this->once())->method('generateFunctionStub');
        $sourceStubber2->expects($this->once())->method('generateFunctionStub');

        self::assertNull((new AggregateSourceStubber($sourceStubber1, $sourceStubber2))->generateFunctionStub('someFunction'));
    }

    public function testTraverseAllGivenSourceStubbersAndSucceedToGenerateFunctionStub() : void
    {
        $sourceStubber1 = $this->createMock(SourceStubber::class);
        $sourceStubber2 = $this->createMock(SourceStubber::class);
        $sourceStubber3 = $this->createMock(SourceStubber::class);
        $sourceStubber4 = $this->createMock(SourceStubber::class);

        $stubData = new StubData('<?php function someFunction () {}', null);

        $sourceStubber1->expects($this->once())->method('generateFunctionStub');
        $sourceStubber2->expects($this->once())->method('generateFunctionStub');
        $sourceStubber3->expects($this->once())->method('generateFunctionStub')->willReturn($stubData);
        $sourceStubber4->expects($this->never())->method('generateFunctionStub');

        self::assertSame(
            $stubData,
            (new AggregateSourceStubber(
                $sourceStubber1,
                $sourceStubber2,
                $sourceStubber3,
                $sourceStubber4
            ))->generateFunctionStub('someFunction')
        );
    }
}
