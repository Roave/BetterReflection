<?php

declare(strict_types=1);

namespace Roave\BetterReflection;

use PhpParser\Parser;
use Roave\BetterReflection\NodeCompiler\CompileNodeToValue;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\Reflector\FunctionReflector;
use Roave\BetterReflection\SourceLocator\Ast\Locator as AstLocator;
use Roave\BetterReflection\SourceLocator\Ast\Strategy\AstConversionStrategy;
use Roave\BetterReflection\SourceLocator\Type\SourceLocator;
use Roave\BetterReflection\Util\FindReflectionOnLine;

interface BetterReflection
{
    public function sourceLocator() : SourceLocator;
    public function classReflector() : ClassReflector;
    public function functionReflector() : FunctionReflector;
    public function phpParser() : Parser;
    public function astLocator() : AstLocator;
    public function astConversionStrategy() : AstConversionStrategy;
    public function compileNodeToValue() : CompileNodeToValue;
    public function findReflectionsOnLine() : FindReflectionOnLine;
}
