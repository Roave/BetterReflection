<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\SourceLocator\Type;

use PhpParser\ParserFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\Reflector\DefaultReflector;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\Ast\Parser\MemoizingParser;
use Roave\BetterReflection\SourceLocator\Type\AutoloadSourceLocator;
use Roave\BetterReflectionTest\Fixture\ExampleClass;

use function class_exists;

#[CoversClass(AutoloadSourceLocator::class)]
class AutoloadSourceLocatorWithoutLoadedParserDependenciesTest extends TestCase
{
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testCanFindClassEvenWhenParserIsNotLoadedInMemory(): void
    {
        self::assertFalse(
            class_exists(MemoizingParser::class, false),
            MemoizingParser::class . ' was not loaded into memory',
        );

        $parser        = (new ParserFactory())->createForNewestSupportedVersion();
        $sourceLocator = new AutoloadSourceLocator(
            new Locator($parser),
            $parser,
        );

        $reflector  = new DefaultReflector($sourceLocator);
        $reflection = $reflector->reflectClass(ExampleClass::class);

        self::assertSame(ExampleClass::class, $reflection->getName());
        self::assertFalse(
            class_exists(MemoizingParser::class, false),
            MemoizingParser::class . ' was not implicitly loaded',
        );
    }
}
