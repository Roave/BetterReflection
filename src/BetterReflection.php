<?php
declare(strict_types=1);

namespace Rector\BetterReflection;

use PhpParser\Lexer\Emulative;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use Rector\BetterReflection\Reflector\ClassReflector;
use Rector\BetterReflection\Reflector\FunctionReflector;
use Rector\BetterReflection\SourceLocator\Ast\Locator as AstLocator;
use Rector\BetterReflection\SourceLocator\Ast\Parser\MemoizingParser;
use Rector\BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use Rector\BetterReflection\SourceLocator\Type\AutoloadSourceLocator;
use Rector\BetterReflection\SourceLocator\Type\EvaledCodeSourceLocator;
use Rector\BetterReflection\SourceLocator\Type\MemoizingSourceLocator;
use Rector\BetterReflection\SourceLocator\Type\PhpInternalSourceLocator;
use Rector\BetterReflection\SourceLocator\Type\SourceLocator;
use Rector\BetterReflection\Util\FindReflectionOnLine;

final class BetterReflection
{
    /**
     * @var SourceLocator|null
     */
    private $sourceLocator;

    /**
     * @var ClassReflector|null
     */
    private $classReflector;

    /**
     * @var FunctionReflector|null
     */
    private $functionReflector;

    /**
     * @var Parser|null
     */
    private $phpParser;

    /**
     * @var AstLocator|null
     */
    private $astLocator;

    /**
     * @var FindReflectionOnLine|null
     */
    private $findReflectionOnLine;

    public function sourceLocator() : SourceLocator
    {
        $astLocator = $this->astLocator();

        return $this->sourceLocator
            ?? $this->sourceLocator = new MemoizingSourceLocator(new AggregateSourceLocator([
                new PhpInternalSourceLocator($astLocator),
                new EvaledCodeSourceLocator($astLocator),
                new AutoloadSourceLocator($astLocator),
            ]));
    }

    public function classReflector() : ClassReflector
    {
        return $this->classReflector
            ?? $this->classReflector = new ClassReflector($this->sourceLocator());
    }

    public function functionReflector() : FunctionReflector
    {
        return $this->functionReflector
            ?? $this->functionReflector = new FunctionReflector($this->sourceLocator(), $this->classReflector());
    }

    public function phpParser() : Parser
    {
        return $this->phpParser
            ?? $this->phpParser = new MemoizingParser(
                (new ParserFactory())->create(ParserFactory::PREFER_PHP7, new Emulative([
                    'usedAttributes' => ['comments', 'startLine', 'endLine', 'startFilePos', 'endFilePos'],
                ]))
            );
    }

    public function astLocator() : AstLocator
    {
        return $this->astLocator
            ?? $this->astLocator = new AstLocator($this->phpParser());
    }

    public function findReflectionsOnLine() : FindReflectionOnLine
    {
        return $this->findReflectionOnLine
            ?? $this->findReflectionOnLine = new FindReflectionOnLine($this->sourceLocator(), $this->astLocator());
    }
}
