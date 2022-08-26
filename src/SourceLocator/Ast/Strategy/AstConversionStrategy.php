<?php

declare(strict_types=1);

namespace Roave\BetterReflection\SourceLocator\Ast\Strategy;

use PhpParser\Node;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionConstant;
use Roave\BetterReflection\Reflection\ReflectionFunction;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\Located\LocatedSource;

/** @internal */
interface AstConversionStrategy
{
    /**
     * Take an AST node in some located source (potentially in a namespace) and
     * convert it to something (concrete implementation decides)
     */
    public function __invoke(
        Reflector $reflector,
        Node\Stmt\Class_|Node\Stmt\Interface_|Node\Stmt\Trait_|Node\Stmt\Enum_|Node\Stmt\Function_|Node\Expr\Closure|Node\Expr\ArrowFunction|Node\Stmt\Const_|Node\Expr\FuncCall $node,
        LocatedSource $locatedSource,
        Node\Stmt\Namespace_|null $namespace,
        int|null $positionInNode = null,
    ): ReflectionClass|ReflectionConstant|ReflectionFunction;
}
