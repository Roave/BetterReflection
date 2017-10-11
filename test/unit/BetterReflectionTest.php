<?php
declare(strict_types=1);

namespace Rector\BetterReflectionTest;

use PhpParser\Parser;
use PHPUnit\Framework\TestCase;
use Rector\BetterReflection\BetterReflection;
use Rector\BetterReflection\Reflector\ClassReflector;
use Rector\BetterReflection\Reflector\FunctionReflector;
use Rector\BetterReflection\SourceLocator\Ast\Locator;
use Rector\BetterReflection\SourceLocator\Type\SourceLocator;
use Rector\BetterReflection\Util\FindReflectionOnLine;

/**
 * @covers \Rector\BetterReflection\BetterReflection
 */
final class BetterReflectionTest extends TestCase
{
    public function testAccessorsReturnTypes() : void
    {
        $betterReflection = new BetterReflection();

        self::assertInstanceOf(Locator::class, $betterReflection->astLocator());
        self::assertInstanceOf(ClassReflector::class, $betterReflection->classReflector());
        self::assertInstanceOf(FunctionReflector::class, $betterReflection->functionReflector());
        self::assertInstanceOf(FindReflectionOnLine::class, $betterReflection->findReflectionsOnLine());
        self::assertInstanceOf(SourceLocator::class, $betterReflection->sourceLocator());
        self::assertInstanceOf(Parser::class, $betterReflection->phpParser());
    }

    public function testProducedInstancesAreMemoized() : void
    {
        $betterReflection = new BetterReflection();

        self::assertSame($betterReflection->astLocator(), $betterReflection->astLocator());
        self::assertSame($betterReflection->classReflector(), $betterReflection->classReflector());
        self::assertSame($betterReflection->functionReflector(), $betterReflection->functionReflector());
        self::assertSame($betterReflection->findReflectionsOnLine(), $betterReflection->findReflectionsOnLine());
        self::assertSame($betterReflection->sourceLocator(), $betterReflection->sourceLocator());
        self::assertSame($betterReflection->phpParser(), $betterReflection->phpParser());
    }

    public function testProducedInstancesAreNotMemoizedAcrossInstances() : void
    {
        $betterReflection1 = new BetterReflection();
        $betterReflection2 = new BetterReflection();

        self::assertNotSame($betterReflection1->astLocator(), $betterReflection2->astLocator());
        self::assertNotSame($betterReflection1->classReflector(), $betterReflection2->classReflector());
        self::assertNotSame($betterReflection1->functionReflector(), $betterReflection2->functionReflector());
        self::assertNotSame($betterReflection1->findReflectionsOnLine(), $betterReflection2->findReflectionsOnLine());
        self::assertNotSame($betterReflection1->sourceLocator(), $betterReflection2->sourceLocator());
        self::assertNotSame($betterReflection1->phpParser(), $betterReflection2->phpParser());
    }
}
