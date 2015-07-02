<?php

namespace BetterReflection\Reflector;

use BetterReflection\Reflection\Symbol;
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
     * Uses the SourceLocator given in the constructor to locate the $symbolName
     * specified and returns the \Reflector
     *
     * @param Symbol $symbol
     * @return Reflection
     */
    public function reflect(Symbol $symbol)
    {
        if ($symbol->isLoaded()) {
            throw new \LogicException(sprintf(
                '%s "%s" is already loaded',
                $symbol->getDisplayType(),
                $symbol->getName()
            ));
        }

        $locatedSource = $this->sourceLocator->__invoke($symbol);
        $reflection = $this->reflectFromLocatedSource($symbol, $locatedSource);

        return $reflection;
    }

    /**
     * Given an array of Reflections, try to find the symbol
     *
     * @param Reflection[] $reflections
     * @param Symbol $symbol
     * @return Reflection
     */
    private function findInArray($reflections, Symbol $symbol)
    {
        foreach ($reflections as $reflection) {
            if ($reflection->getName() == $symbol->getName()) {
                return $reflection;
            }
        }

        throw new \UnexpectedValueException(sprintf(
            '%s "%s" could not be found to load',
            $symbol->getDisplayType(),
            $symbol->getName()
        ));
    }

    /**
     * Read all the symbols from a LocatedSource and find the specified symbol
     *
     * @param Symbol $symbol
     * @param LocatedSource $locatedSource
     * @return Reflection
     */
    private function reflectFromLocatedSource(
        Symbol $symbol,
        LocatedSource $locatedSource
    ) {
        $reflections = $this->getReflections($locatedSource, $symbol);
        return $this->findInArray($reflections, $symbol);
    }

    /**
     * @param Node $node
     * @return Reflection|null
     */
    private function reflectNode(Node $node, Node\Stmt\Namespace_ $namespace = null, $filename = null)
    {
        if ($node instanceof Node\Stmt\Class_) {
            return ReflectionClass::createFromNode(
                $node,
                $namespace,
                $filename
            );
        }

        return null;
    }

    /**
     * Process and reflect all the matching symbols found inside a namespace node
     *
     * @param Node\Stmt\Namespace_ $namespace
     * @param Symbol $symbol
     * @param string|null $filename
     * @return Reflection[]
     */
    private function reflectFromNamespace(
        Node\Stmt\Namespace_ $namespace,
        Symbol $symbol,
        $filename
    ) {
        $reflections = [];
        foreach ($namespace->stmts as $node) {
            $reflection = $this->reflectNode($node, $namespace, $filename);

            if (null !== $reflection && $symbol->isMatchingReflector($reflection)) {
                $reflections[] = $reflection;
            }

        }
        return $reflections;
    }

    /**
     * Reflect symbols from an AST. If a namespace is found, also load all the
     * matching symbols found in the namespace
     *
     * @param Node[] $ast
     * @param string|null $filename
     * @param Symbol $symbol
     * @return Reflection[]
     */
    private function reflectFromTree(array $ast, $filename, Symbol $symbol)
    {
        $reflections = [];
        foreach ($ast as $node) {
            switch (get_class($node)) {
                case Node\Stmt\Namespace_::class:
                    $reflections = array_merge(
                        $reflections,
                        $this->reflectFromNamespace($node, $symbol, $filename)
                    );
                    break;
                case Node\Stmt\Class_::class:
                    $reflection = $this->reflectNode($node, null, $filename);
                    if ($symbol->isMatchingReflector($reflection)) {
                        $reflections[] = $reflection;
                    }
                    break;
            }
        }
        return $reflections;
    }

    /**
     * Get an array of reflections found in a LocatedSource
     *
     * @param LocatedSource $locatedSource
     * @param Symbol $symbol
     * @return Reflection[]
     */
    private function getReflections(LocatedSource $locatedSource, Symbol $symbol)
    {
        $parser = new Parser(new Lexer);
        $ast = $parser->parse($locatedSource->getSource());

        return $this->reflectFromTree(
            $ast,
            $locatedSource->getFileName(),
            $symbol
        );
    }

    /**
     * Get all symbols of a matching symbol type from a file
     *
     * @param string $symbolType
     * @return Reflection[]
     */
    public function getAllSymbols($symbolType)
    {
        $symbol = new Symbol('*', $symbolType);

        return $this->getReflections(
            $this->sourceLocator->__invoke($symbol),
            $symbol
        );
    }
}
