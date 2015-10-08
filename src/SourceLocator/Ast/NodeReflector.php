<?php

namespace BetterReflection\SourceLocator\Ast;

use BetterReflection\Reflector\Reflector;
use BetterReflection\SourceLocator\Located\DefiniteLocatedSource;
use BetterReflection\Reflection\ReflectionClass;
use BetterReflection\Reflection\ReflectionFunction;
use BetterReflection\Reflection\Reflection;
use PhpParser\Node;

/**
 * @internal
 */
class NodeReflector
{
    /**
     * @var Reflector
     */
    private $reflector;

    /**
     * @param Reflector $reflector
     */
    public function __construct(Reflector $reflector)
    {
        $this->reflector = $reflector;
    }

    /**
     * @param Node $node
     * @param DefiniteLocatedSource $locatedSource
     * @param Node\Stmt\Namespace_|null $namespace
     * @return Reflection|null
     */
    public function __invoke(Node $node, DefiniteLocatedSource $locatedSource, Node\Stmt\Namespace_ $namespace = null)
    {
        if ($node instanceof Node\Stmt\ClassLike) {
            return ReflectionClass::createFromNode(
                $this->reflector,
                $node,
                $locatedSource,
                $namespace
            );
        }

        if ($node instanceof Node\Stmt\Function_) {
            return ReflectionFunction::createFromNode(
                $this->reflector,
                $node,
                $locatedSource,
                $namespace
            );
        }

        return null;
    }
}
