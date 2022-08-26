<?php

declare(strict_types=1);

namespace Roave\BetterReflection;

use PhpParser\Lexer\Emulative;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use Roave\BetterReflection\Reflector\DefaultReflector;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\Ast\Locator as AstLocator;
use Roave\BetterReflection\SourceLocator\Ast\Parser\MemoizingParser;
use Roave\BetterReflection\SourceLocator\SourceStubber\AggregateSourceStubber;
use Roave\BetterReflection\SourceLocator\SourceStubber\PhpStormStubsSourceStubber;
use Roave\BetterReflection\SourceLocator\SourceStubber\ReflectionSourceStubber;
use Roave\BetterReflection\SourceLocator\SourceStubber\SourceStubber;
use Roave\BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\AutoloadSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\EvaledCodeSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\MemoizingSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\PhpInternalSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\SourceLocator;
use Roave\BetterReflection\Util\FindReflectionOnLine;

final class BetterReflection
{
    private SourceLocator|null $sourceLocator = null;

    private Reflector|null $reflector = null;

    private Parser|null $phpParser = null;

    private AstLocator|null $astLocator = null;

    private FindReflectionOnLine|null $findReflectionOnLine = null;

    private SourceStubber|null $sourceStubber = null;

    public function sourceLocator(): SourceLocator
    {
        $astLocator    = $this->astLocator();
        $sourceStubber = $this->sourceStubber();

        return $this->sourceLocator
            ?? $this->sourceLocator = new MemoizingSourceLocator(new AggregateSourceLocator([
                new PhpInternalSourceLocator($astLocator, $sourceStubber),
                new EvaledCodeSourceLocator($astLocator, $sourceStubber),
                new AutoloadSourceLocator($astLocator, $this->phpParser()),
            ]));
    }

    public function reflector(): Reflector
    {
        return $this->reflector
            ?? $this->reflector = new DefaultReflector($this->sourceLocator());
    }

    public function phpParser(): Parser
    {
        return $this->phpParser
            ?? $this->phpParser = new MemoizingParser(
                (new ParserFactory())->create(ParserFactory::ONLY_PHP7, new Emulative([
                    'usedAttributes' => ['comments', 'startLine', 'endLine', 'startFilePos', 'endFilePos'],
                ])),
            );
    }

    public function astLocator(): AstLocator
    {
        return $this->astLocator
            ?? $this->astLocator = new AstLocator($this->phpParser());
    }

    public function findReflectionsOnLine(): FindReflectionOnLine
    {
        return $this->findReflectionOnLine
            ?? $this->findReflectionOnLine = new FindReflectionOnLine($this->sourceLocator(), $this->astLocator());
    }

    public function sourceStubber(): SourceStubber
    {
        return $this->sourceStubber
            ?? $this->sourceStubber = new AggregateSourceStubber(
                new PhpStormStubsSourceStubber($this->phpParser()),
                new ReflectionSourceStubber(),
            );
    }
}
