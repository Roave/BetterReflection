<?php

namespace BetterReflection\Reflector;

use BetterReflection\Identifier\Identifier;
use BetterReflection\Identifier\IdentifierType;
use BetterReflection\Reflection\ReflectionFunction;
use BetterReflection\SourceLocator\LocatedSource;
use BetterReflection\SourceLocator\SourceLocator;
use BetterReflection\Reflection\ReflectionClass;
use BetterReflection\Reflection\Reflection;
use PhpParser\Parser;
use PhpParser\Lexer;
use PhpParser\Node;

class Generic
{
    /**
     * @var SourceLocator
     */
    private $sourceLocator;

    public function __construct(SourceLocator $sourceLocator)
    {
        $this->sourceLocator = $sourceLocator;
    }

    /**
     * Uses the SourceLocator given in the constructor to locate the $identifier
     * specified and returns the \Reflector.
     *
     * @param Identifier $identifier
     * @return Reflection
     */
    public function reflect(Identifier $identifier)
    {
        return $this->reflectFromLocatedSource(
            $identifier,
            $this->sourceLocator->__invoke($identifier)
        );
    }

    /**
     * Given an array of Reflections, try to find the identifier.
     *
     * @param Reflection[] $reflections
     * @param Identifier $identifier
     * @return Reflection
     */
    private function findInArray($reflections, Identifier $identifier)
    {
        foreach ($reflections as $reflection) {
            if ($reflection->getName() === $identifier->getName()) {
                return $reflection;
            }
        }

        throw new Exception\IdentifierNotFound(sprintf(
            '%s "%s" could not be found in the located source',
            $identifier->getType()->getName(),
            $identifier->getName()
        ));
    }

    /**
     * Read all the identifiers from a LocatedSource and find the specified identifier.
     *
     * @param Identifier $identifier
     * @param LocatedSource $locatedSource
     * @return Reflection
     */
    private function reflectFromLocatedSource(
        Identifier $identifier,
        LocatedSource $locatedSource
    ) {
        $reflections = $this->getReflections($locatedSource, $identifier);
        return $this->findInArray($reflections, $identifier);
    }

    /**
     * @param Node $node
     * @param LocatedSource $locatedSource
     * @param Node\Stmt\Namespace_|null $namespace
     * @return Reflection|null
     */
    private function reflectNode(Node $node, LocatedSource $locatedSource, Node\Stmt\Namespace_ $namespace = null)
    {
        if ($node instanceof Node\Stmt\Class_) {
            return ReflectionClass::createFromNode(
                $node,
                $locatedSource,
                $namespace
            );
        }

        if ($node instanceof Node\Stmt\Function_) {
            return ReflectionFunction::createFromNode(
                $node,
                $locatedSource,
                $namespace
            );
        }

        return null;
    }

    /**
     * Process and reflect all the matching identifiers found inside a namespace node.
     *
     * @param Node\Stmt\Namespace_ $namespace
     * @param Identifier $identifier
     * @param LocatedSource $locatedSource
     * @return Reflection[]
     */
    private function reflectFromNamespace(
        Node\Stmt\Namespace_ $namespace,
        Identifier $identifier,
        LocatedSource $locatedSource
    ) {
        $reflections = [];
        foreach ($namespace->stmts as $node) {
            $reflection = $this->reflectNode($node, $locatedSource, $namespace);

            if (null !== $reflection && $identifier->getType()->isMatchingReflector($reflection)) {
                $reflections[] = $reflection;
            }
        }
        return $reflections;
    }

    /**
     * Reflect identifiers from an AST. If a namespace is found, also load all the
     * matching identifiers found in the namespace.
     *
     * @param Node[] $ast
     * @param Identifier $identifier
     * @param LocatedSource $locatedSource
     * @return \BetterReflection\Reflection\Reflection[]
     */
    private function reflectFromTree(array $ast, Identifier $identifier, LocatedSource $locatedSource)
    {
        $reflections = [];
        foreach ($ast as $node) {
            if ($node instanceof Node\Stmt\Namespace_) {
                $reflections = array_merge(
                    $reflections,
                    $this->reflectFromNamespace($node, $identifier, $locatedSource)
                );
            } elseif ($node instanceof Node\Stmt\Class_) {
                $reflection = $this->reflectNode($node, $locatedSource, null);
                if ($identifier->getType()->isMatchingReflector($reflection)) {
                    $reflections[] = $reflection;
                }
            } elseif ($node instanceof Node\Stmt\Function_) {
                $reflection = $this->reflectNode($node, $locatedSource, null);
                if ($identifier->getType()->isMatchingReflector($reflection)) {
                    $reflections[] = $reflection;
                }
            }
        }
        return $reflections;
    }

    /**
     * Get an array of reflections found in a LocatedSource.
     *
     * @param LocatedSource $locatedSource
     * @param Identifier $identifier
     * @return Reflection[]
     */
    private function getReflections(LocatedSource $locatedSource, Identifier $identifier)
    {
        return $this->reflectFromTree(
            (new Parser(new Lexer()))->parse($locatedSource->getSource()),
            $identifier,
            $locatedSource
        );
    }

    /**
     * Get all identifiers of a matching identifier type from a file.
     *
     * @param IdentifierType $identifierType
     * @return Reflection[]
     */
    public function getAllByIdentifierType(IdentifierType $identifierType)
    {
        $identifier = new Identifier('*', $identifierType);

        return $this->getReflections(
            $this->sourceLocator->__invoke($identifier),
            $identifier
        );
    }
}
