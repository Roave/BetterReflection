<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\SourceLocator\SourceStubber;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\SourceLocator\SourceStubber\AggregateSourceStubber;
use Roave\BetterReflection\SourceLocator\SourceStubber\SourceStubber;
use Roave\BetterReflection\SourceLocator\SourceStubber\StubData;

#[CoversClass(AggregateSourceStubber::class)]
class AggregateSourceStubberTest extends TestCase
{
    public function testTraverseAllGivenSourceStubbersAndFailToGenerateClassStub(): void
    {
        $sourceStubber1 = $this->createMock(SourceStubber::class);
        $sourceStubber2 = $this->createMock(SourceStubber::class);

        $sourceStubber1->expects($this->once())->method('generateClassStub');
        $sourceStubber2->expects($this->once())->method('generateClassStub');

        /** @phpstan-var class-string $someClassName */
        $someClassName = 'SomeClass';

        self::assertNull((new AggregateSourceStubber($sourceStubber1, $sourceStubber2))->generateClassStub($someClassName));
    }

    public function testTraverseAllGivenSourceStubbersAndSucceedToGenerateClassStub(): void
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

        /** @phpstan-var class-string $someClassName */
        $someClassName = 'SomeClass';

        self::assertSame(
            $stubData,
            (new AggregateSourceStubber(
                $sourceStubber1,
                $sourceStubber2,
                $sourceStubber3,
                $sourceStubber4,
            ))->generateClassStub($someClassName),
        );
    }

    public function testTraverseAllGivenSourceStubbersAndFailToGenerateFunctionStub(): void
    {
        $sourceStubber1 = $this->createMock(SourceStubber::class);
        $sourceStubber2 = $this->createMock(SourceStubber::class);

        $sourceStubber1->expects($this->once())->method('generateFunctionStub');
        $sourceStubber2->expects($this->once())->method('generateFunctionStub');

        self::assertNull((new AggregateSourceStubber($sourceStubber1, $sourceStubber2))->generateFunctionStub('someFunction'));
    }

    public function testTraverseAllGivenSourceStubbersAndSucceedToGenerateFunctionStub(): void
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
                $sourceStubber4,
            ))->generateFunctionStub('someFunction'),
        );
    }

    public function testTraverseAllGivenSourceStubbersAndFailToGenerateConstantStub(): void
    {
        $sourceStubber1 = $this->createMock(SourceStubber::class);
        $sourceStubber2 = $this->createMock(SourceStubber::class);

        $sourceStubber1->expects($this->once())->method('generateConstantStub');
        $sourceStubber2->expects($this->once())->method('generateConstantStub');

        self::assertNull((new AggregateSourceStubber($sourceStubber1, $sourceStubber2))->generateConstantStub('SOME_CONSTANT'));
    }

    public function testTraverseAllGivenSourceStubbersAndSucceedToGenerateConstantStub(): void
    {
        $sourceStubber1 = $this->createMock(SourceStubber::class);
        $sourceStubber2 = $this->createMock(SourceStubber::class);
        $sourceStubber3 = $this->createMock(SourceStubber::class);
        $sourceStubber4 = $this->createMock(SourceStubber::class);

        $stubData = new StubData('<?php const SOME_CONSTANT = 1;', null);

        $sourceStubber1->expects($this->once())->method('generateConstantStub');
        $sourceStubber2->expects($this->once())->method('generateConstantStub');
        $sourceStubber3->expects($this->once())->method('generateConstantStub')->willReturn($stubData);
        $sourceStubber4->expects($this->never())->method('generateConstantStub');

        self::assertSame(
            $stubData,
            (new AggregateSourceStubber(
                $sourceStubber1,
                $sourceStubber2,
                $sourceStubber3,
                $sourceStubber4,
            ))->generateConstantStub('SOME_CONSTANT'),
        );
    }
}
