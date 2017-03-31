<?php

namespace Roave\BetterReflection\SourceLocator\Ast;

use Roave\BetterReflection\Identifier\IdentifierType;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\Ast\Strategy\AstConversionStrategy;
use Roave\BetterReflection\SourceLocator\Located\LocatedSource;
use Roave\BetterReflection\Reflection\Reflection;
use PhpParser\Node;

/**
 * @internal
 */
final class FindReflectionsInTree
{
    /**
     * @var AstConversionStrategy
     */
    private $astConversionStrategy;

    public function __construct(AstConversionStrategy $astConversionStrategy)
    {
        $this->astConversionStrategy = $astConversionStrategy;
    }

    /**
     * Find all reflections of type in an Abstract Syntax Tree
     *
     * @param Reflector $reflector
     * @param array $ast
     * @param IdentifierType $identifierType
     * @param LocatedSource $locatedSource
     * @return \Roave\BetterReflection\Reflection\Reflection[]
     */
    public function __invoke(Reflector $reflector, array $ast, IdentifierType $identifierType, LocatedSource $locatedSource) : array
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
        return $this->astConversionStrategy->__invoke($reflector, $node, $locatedSource, $namespace);
    }

    /**
     * Process and reflect all the matching identifiers found inside a namespace node.
     *
     * @param Reflector $reflector
     * @param Node\Stmt\Namespace_ $namespace
     * @param IdentifierType $identifierType
     * @param LocatedSource $locatedSource
     * @return \Roave\BetterReflection\Reflection\Reflection[]
     */
    private function reflectFromNamespace(
        Reflector $reflector,
        Node\Stmt\Namespace_ $namespace,
        IdentifierType $identifierType,
        LocatedSource $locatedSource
    ) : array {
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
     * @return \Roave\BetterReflection\Reflection\Reflection[]
     */
    private function reflectFromTree(Reflector $reflector, array $ast, IdentifierType $identifierType, LocatedSource $locatedSource) : array
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
