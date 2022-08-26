<?php

declare(strict_types=1);

namespace Roave\BetterReflection\SourceLocator\Ast\Strategy;

use PhpParser\Node;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionConstant;
use Roave\BetterReflection\Reflection\ReflectionEnum;
use Roave\BetterReflection\Reflection\ReflectionFunction;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\Located\LocatedSource;

/** @internal */
class NodeToReflection implements AstConversionStrategy
{
    /**
     * Take an AST node in some located source (potentially in a namespace) and
     * convert it to a Reflection
     */
    public function __invoke(
        Reflector $reflector,
        Node\Stmt\Class_|Node\Stmt\Interface_|Node\Stmt\Trait_|Node\Stmt\Enum_|Node\Stmt\Function_|Node\Expr\Closure|Node\Expr\ArrowFunction|Node\Stmt\Const_|Node\Expr\FuncCall $node,
        LocatedSource $locatedSource,
        Node\Stmt\Namespace_|null $namespace,
        int|null $positionInNode = null,
    ): ReflectionClass|ReflectionConstant|ReflectionFunction {
        if ($node instanceof Node\Stmt\Enum_) {
            return ReflectionEnum::createFromNode(
                $reflector,
                $node,
                $locatedSource,
                $namespace,
            );
        }

        if ($node instanceof Node\Stmt\ClassLike) {
            return ReflectionClass::createFromNode(
                $reflector,
                $node,
                $locatedSource,
                $namespace,
            );
        }

        if (
            $node instanceof Node\Stmt\Function_
            || $node instanceof Node\Expr\Closure
            || $node instanceof Node\Expr\ArrowFunction
        ) {
            return ReflectionFunction::createFromNode(
                $reflector,
                $node,
                $locatedSource,
                $namespace,
            );
        }

        if ($node instanceof Node\Stmt\Const_) {
            return ReflectionConstant::createFromNode($reflector, $node, $locatedSource, $namespace, $positionInNode);
        }

        return ReflectionConstant::createFromNode($reflector, $node, $locatedSource);
    }
}
