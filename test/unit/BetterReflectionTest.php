<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest;

use PhpParser\Node;
use PhpParser\Parser;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\SourceStubber\SourceStubber;
use Roave\BetterReflection\SourceLocator\Type\SourceLocator;
use Roave\BetterReflection\Util\FindReflectionOnLine;

/** @covers \Roave\BetterReflection\BetterReflection */
final class BetterReflectionTest extends TestCase
{
    public function testAccessorsReturnTypes(): void
    {
        $betterReflection = new BetterReflection();

        self::assertInstanceOf(Locator::class, $betterReflection->astLocator());
        self::assertInstanceOf(Reflector::class, $betterReflection->reflector());
        self::assertInstanceOf(FindReflectionOnLine::class, $betterReflection->findReflectionsOnLine());
        self::assertInstanceOf(SourceLocator::class, $betterReflection->sourceLocator());
        self::assertInstanceOf(Parser::class, $betterReflection->phpParser());
        self::assertInstanceOf(SourceStubber::class, $betterReflection->sourceStubber());
    }

    public function testProducedInstancesAreMemoized(): void
    {
        $betterReflection = new BetterReflection();

        self::assertSame($betterReflection->astLocator(), $betterReflection->astLocator());
        self::assertSame($betterReflection->reflector(), $betterReflection->reflector());
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
        self::assertNotSame($betterReflection1->reflector(), $betterReflection2->reflector());
        self::assertNotSame($betterReflection1->findReflectionsOnLine(), $betterReflection2->findReflectionsOnLine());
        self::assertNotSame($betterReflection1->sourceLocator(), $betterReflection2->sourceLocator());
        self::assertNotSame($betterReflection1->phpParser(), $betterReflection2->phpParser());
        self::assertNotSame($betterReflection1->sourceStubber(), $betterReflection2->sourceStubber());
    }

    public function testPhpParserHasAllRequiredSettings(): void
    {
        $phpParser = (new BetterReflection())->phpParser();
        $phpCode   = <<<'PHP'
<?php

/**
 * Comment
 */
class Foo
{
}
PHP;

        $ast = $phpParser->parse($phpCode);

        self::assertNotNull($ast);
        self::assertArrayHasKey(0, $ast);
        self::assertInstanceOf(Node\Stmt\Class_::class, $ast[0]);

        self::assertTrue($ast[0]->hasAttribute('comments'));
        self::assertTrue($ast[0]->hasAttribute('startLine'));
        self::assertTrue($ast[0]->hasAttribute('endLine'));
        self::assertTrue($ast[0]->hasAttribute('startFilePos'));
        self::assertTrue($ast[0]->hasAttribute('endFilePos'));
    }
}
