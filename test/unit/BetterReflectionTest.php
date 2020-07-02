<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest;

use PhpParser\Parser;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\Reflector\ConstantReflector;
use Roave\BetterReflection\Reflector\FunctionReflector;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\SourceStubber\SourceStubber;
use Roave\BetterReflection\SourceLocator\Type\SourceLocator;
use Roave\BetterReflection\Util\FindReflectionOnLine;

/**
 * @covers \Roave\BetterReflection\BetterReflection
 */
final class BetterReflectionTest extends TestCase
{
    public function testAccessorsReturnTypes(): void
    {
        $betterReflection = new BetterReflection();

        self::assertInstanceOf(Locator::class, $betterReflection->astLocator());
        self::assertInstanceOf(ClassReflector::class, $betterReflection->classReflector());
        self::assertInstanceOf(FunctionReflector::class, $betterReflection->functionReflector());
        self::assertInstanceOf(ConstantReflector::class, $betterReflection->constantReflector());
        self::assertInstanceOf(FindReflectionOnLine::class, $betterReflection->findReflectionsOnLine());
        self::assertInstanceOf(SourceLocator::class, $betterReflection->sourceLocator());
        self::assertInstanceOf(Parser::class, $betterReflection->phpParser());
        self::assertInstanceOf(SourceStubber::class, $betterReflection->sourceStubber());
    }

    public function testProducedInstancesAreMemoized(): void
    {
        $betterReflection = new BetterReflection();

        self::assertSame($betterReflection->astLocator(), $betterReflection->astLocator());
        self::assertSame($betterReflection->classReflector(), $betterReflection->classReflector());
        self::assertSame($betterReflection->functionReflector(), $betterReflection->functionReflector());
        self::assertSame($betterReflection->constantReflector(), $betterReflection->constantReflector());
        self::assertSame($betterReflection->findReflectionsOnLine(), $betterReflection->findReflectionsOnLine());
        self::assertSame($betterReflection->sourceLocator(), $betterReflection->sourceLocator());
        self::assertSame($betterReflection->phpParser(), $betterReflection->phpParser());
        self::assertSame($betterReflection->sourceStubber(), $betterReflection->sourceStubber());
    }

    public function testProducedInstancesAreNotMemoizedAcrossInstances(): void
    {
        $betterReflection1 = new BetterReflection();
        $betterReflection2 = new BetterReflection();

        self::assertNotSame($betterReflection1->astLocator(), $betterReflection2->astLocator());
        self::assertNotSame($betterReflection1->classReflector(), $betterReflection2->classReflector());
        self::assertNotSame($betterReflection1->functionReflector(), $betterReflection2->functionReflector());
        self::assertNotSame($betterReflection1->constantReflector(), $betterReflection2->constantReflector());
        self::assertNotSame($betterReflection1->findReflectionsOnLine(), $betterReflection2->findReflectionsOnLine());
        self::assertNotSame($betterReflection1->sourceLocator(), $betterReflection2->sourceLocator());
        self::assertNotSame($betterReflection1->phpParser(), $betterReflection2->phpParser());
        self::assertNotSame($betterReflection1->sourceStubber(), $betterReflection2->sourceStubber());
    }
}
