<?php

declare(strict_types=1);

namespace Roave\BetterReflection;

use PhpParser\Lexer\Emulative;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use Roave\BetterReflection\NodeCompiler\CompileNodeToValue;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\Reflector\FunctionReflector;
use Roave\BetterReflection\SourceLocator\Ast\Locator as AstLocator;
use Roave\BetterReflection\SourceLocator\Ast\Strategy\AstConversionStrategy;
use Roave\BetterReflection\SourceLocator\Ast\Strategy\NodeToReflection;
use Roave\BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\AutoloadSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\EvaledCodeSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\PhpInternalSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\SourceLocator;
use Roave\BetterReflection\Util\FindReflectionOnLine;

// @TODO should the naming be swapped around? `new BetterReflection()` sounds MUCH cooler!
final class Configuration implements BetterReflection
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
     * @var AstConversionStrategy|null
     */
    private $astConversionStrategy;

    /**
     * @var CompileNodeToValue|null
     */
    private $compileNodeToValue;

    /**
     * @var FindReflectionOnLine|null
     */
    private $findReflectionOnLine;

    public function sourceLocator() : SourceLocator
    {
        return $this->sourceLocator
            ?? $this->sourceLocator = new AggregateSourceLocator([
                new PhpInternalSourceLocator(),
                new EvaledCodeSourceLocator(),
                new AutoloadSourceLocator(),
            ]);
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
            ?? $this->phpParser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7, new Emulative([
                'usedAttributes' => ['comments', 'startLine', 'endLine', 'startFilePos', 'endFilePos'],
            ]));
    }

    public function astLocator() : AstLocator
    {
        return $this->astLocator
            ?? $this->astLocator = new AstLocator($this->phpParser());
    }

    public function astConversionStrategy() : AstConversionStrategy
    {
        return $this->astConversionStrategy
            ?? $this->astConversionStrategy = new NodeToReflection();
    }

    public function compileNodeToValue() : CompileNodeToValue
    {
        return $this->compileNodeToValue
            ?? $this->compileNodeToValue = new CompileNodeToValue();
    }

    public function findReflectionsOnLine() : FindReflectionOnLine
    {
        return $this->findReflectionOnLine
            ?? $this->findReflectionOnLine = new FindReflectionOnLine($this->sourceLocator());
    }

    // @TODO modifier methods here. They should be `withFoo()`, `withBar()`, and be immutable.
}
