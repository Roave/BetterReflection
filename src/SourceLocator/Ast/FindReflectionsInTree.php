<?php

namespace BetterReflection\SourceLocator\Ast;

use BetterReflection\Identifier\IdentifierType;
use BetterReflection\Reflector\Reflector;
use BetterReflection\SourceLocator\Ast\Strategy\NodeToReflection;
use BetterReflection\SourceLocator\Located\LocatedSource;
use BetterReflection\Reflection\Reflection;
use PhpParser\Node;
use PhpParser\Parser;
use PhpParser\Lexer;

class FindReflectionsInTree
{
    /**
     * @var NodeToReflection
     */
    private $nodeToReflection;

    public function __construct(NodeToReflection $nodeToReflection)
    {
        $this->nodeToReflection = $nodeToReflection;
    }

    /**
     * Find all reflections of type in an Abstract Syntax Tree
     *
     * @param Reflector $reflector
     * @param array $ast
     * @param IdentifierType $identifierType
     * @param LocatedSource $locatedSource
     * @return \BetterReflection\Reflection\Reflection[]
     */
    public function __invoke(Reflector $reflector, array $ast, IdentifierType $identifierType, LocatedSource $locatedSource)
    {
        return $this->reflectFromTree($reflector, $ast, $identifierType, $locatedSource);
    }

    /**
     * @param Reflector $reflector
     * @param Node $node
     * @param LocatedSource $locatedSource
     * @param Node\Stmt\Namespace_|null $namespace
     * @return Reflection|null
     */
    private function reflectNode(Reflector $reflector, Node $node, LocatedSource $locatedSource, Node\Stmt\Namespace_ $namespace = null)
    {
        return $this->nodeToReflection->__invoke($reflector, $node, $locatedSource, $namespace);
    }

    /**
     * Process and reflect all the matching identifiers found inside a namespace node.
     *
     * @param Reflector $reflector
     * @param Node\Stmt\Namespace_ $namespace
     * @param IdentifierType $identifierType
     * @param LocatedSource $locatedSource
     * @return \BetterReflection\Reflection\Reflection[]
     */
    private function reflectFromNamespace(
        Reflector $reflector,
        Node\Stmt\Namespace_ $namespace,
        IdentifierType $identifierType,
        LocatedSource $locatedSource
    ) {
        $reflections = [];
        foreach ($namespace->stmts as $node) {
            $reflection = $this->reflectNode($reflector, $node, $locatedSource, $namespace);

            if (null !== $reflection && $identifierType->isMatchingReflector($reflection)) {
                $reflections[] = $reflection;
            }
        }
        return $reflections;
    }

    /**
     * Reflect identifiers from an AST. If a namespace is found, also load all the
     * matching identifiers found in the namespace.
     *
     * @param Reflector $reflector
     * @param Node[] $ast
     * @param IdentifierType $identifierType
     * @param LocatedSource $locatedSource
     * @return \BetterReflection\Reflection\Reflection[]
     */
    private function reflectFromTree(Reflector $reflector, array $ast, IdentifierType $identifierType, LocatedSource $locatedSource)
    {
        $reflections = [];
        foreach ($ast as $node) {
            if ($node instanceof Node\Stmt\Namespace_) {
                $reflections = array_merge(
                    $reflections,
                    $this->reflectFromNamespace($reflector, $node, $identifierType, $locatedSource)
                );
            } elseif ($node instanceof Node\Stmt\ClassLike) {
                $reflection = $this->reflectNode($reflector, $node, $locatedSource, null);
                if ($identifierType->isMatchingReflector($reflection)) {
                    $reflections[] = $reflection;
                }
            } elseif ($node instanceof Node\Stmt\Function_) {
                $reflection = $this->reflectNode($reflector, $node, $locatedSource, null);
                if ($identifierType->isMatchingReflector($reflection)) {
                    $reflections[] = $reflection;
                }
            }
        }
        return $reflections;
    }
}
