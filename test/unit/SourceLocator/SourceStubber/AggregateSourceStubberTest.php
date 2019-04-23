<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\SourceLocator\SourceStubber;

use PHPUnit\Framework\TestCase;
use ReflectionClass as CoreReflectionClass;
use ReflectionFunction as CoreReflectionFunction;
use Roave\BetterReflection\SourceLocator\SourceStubber\AggregateSourceStubber;
use Roave\BetterReflection\SourceLocator\SourceStubber\SourceStubber;

/**
 * @covers \Roave\BetterReflection\SourceLocator\SourceStubber\AggregateSourceStubber
 */
class AggregateSourceStubberTest extends TestCase
{
    public function testTraverseAllGivenSourceStubbersAndFailToGenerateClassStub() : void
    {
        $sourceStubber1 = $this->createMock(SourceStubber::class);
        $sourceStubber2 = $this->createMock(SourceStubber::class);

        $reflection = $this->createClassReflection();

        $sourceStubber1->expects($this->once())->method('generateClassStub');
        $sourceStubber2->expects($this->once())->method('generateClassStub');

        self::assertNull((new AggregateSourceStubber($sourceStubber1, $sourceStubber2))->generateClassStub($reflection));
    }

    public function testTraverseAllGivenSourceStubbersAndSucceedToGenerateClassStub() : void
    {
        $reflection = $this->createClassReflection();

        $sourceStubber1 = $this->createMock(SourceStubber::class);
        $sourceStubber2 = $this->createMock(SourceStubber::class);
        $sourceStubber3 = $this->createMock(SourceStubber::class);
        $sourceStubber4 = $this->createMock(SourceStubber::class);

        $stub = '<?php class Source {}';

        $sourceStubber1->expects($this->once())->method('generateClassStub');
        $sourceStubber2->expects($this->once())->method('generateClassStub');
        $sourceStubber3->expects($this->once())->method('generateClassStub')->willReturn($stub);
        $sourceStubber4->expects($this->never())->method('generateClassStub');

        self::assertSame(
            $stub,
            (new AggregateSourceStubber(
                $sourceStubber1,
                $sourceStubber2,
                $sourceStubber3,
                $sourceStubber4
            ))->generateClassStub($reflection)
        );
    }

    public function testTraverseAllGivenSourceStubbersAndFailToGenerateFunctionStub() : void
    {
        $sourceStubber1 = $this->createMock(SourceStubber::class);
        $sourceStubber2 = $this->createMock(SourceStubber::class);

        $reflection = $this->createFunctionReflection();

        $sourceStubber1->expects($this->once())->method('generateFunctionStub');
        $sourceStubber2->expects($this->once())->method('generateFunctionStub');

        self::assertNull((new AggregateSourceStubber($sourceStubber1, $sourceStubber2))->generateFunctionStub($reflection));
    }

    public function testTraverseAllGivenSourceStubbersAndSucceedToGenerateFunctionStub() : void
    {
        $reflection = $this->createFunctionReflection();

        $sourceStubber1 = $this->createMock(SourceStubber::class);
        $sourceStubber2 = $this->createMock(SourceStubber::class);
        $sourceStubber3 = $this->createMock(SourceStubber::class);
        $sourceStubber4 = $this->createMock(SourceStubber::class);

        $stub = '<?php function source () {}';

        $sourceStubber1->expects($this->once())->method('generateFunctionStub');
        $sourceStubber2->expects($this->once())->method('generateFunctionStub');
        $sourceStubber3->expects($this->once())->method('generateFunctionStub')->willReturn($stub);
        $sourceStubber4->expects($this->never())->method('generateFunctionStub');

        self::assertSame(
            $stub,
            (new AggregateSourceStubber(
                $sourceStubber1,
                $sourceStubber2,
                $sourceStubber3,
                $sourceStubber4
            ))->generateFunctionStub($reflection)
        );
    }

    private function createClassReflection() : CoreReflectionClass
    {
        $reflection = $this->createMock(CoreReflectionClass::class);
        $reflection->method('isUserDefined')
            ->willReturn(false);
        $reflection->method('isInternal')
            ->willReturn(true);
        $reflection->method('getExtensionName')
            ->willReturn('SomeExtension');

        return $reflection;
    }

    private function createFunctionReflection() : CoreReflectionFunction
    {
        $reflection = $this->createMock(CoreReflectionFunction::class);
        $reflection->method('isUserDefined')
            ->willReturn(false);
        $reflection->method('isInternal')
            ->willReturn(true);
        $reflection->method('getExtensionName')
            ->willReturn('SomeExtension');

        return $reflection;
    }
}
