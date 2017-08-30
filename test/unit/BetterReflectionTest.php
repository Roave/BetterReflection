<?php
declare(strict_types=1);

namespace Roave\BetterReflectionTest;

use PhpParser\Parser;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\Reflector\FunctionReflector;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\Type\SourceLocator;
use Roave\BetterReflection\Util\FindReflectionOnLine;

/**
 * @covers \Roave\BetterReflection\BetterReflection
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
}
