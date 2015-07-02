<?php

namespace BetterReflection;

use BetterReflection\Reflection\Symbol;
use BetterReflection\SourceLocator\SingleFileSourceLocator;
use BetterReflection\SourceLocator\LocatedSource;
use BetterReflection\SourceLocator\SourceLocator;
use BetterReflection\Reflection\ReflectionClass;
use BetterReflection\Reflection\BetterReflector;
use PhpParser\Parser;
use PhpParser\Lexer;
use PhpParser\Node;

class Reflector
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
     * @param string $name
     * @param string $type
     * @return BetterReflector
     */
    public function reflect($name, $type = Symbol::SYMBOL_CLASS)
    {
        $symbol = new Symbol($name, $type);

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
     * Given an array of BetterReflector, try to find the symbol
     *
     * @param BetterReflector[] $reflections
     * @param Symbol $symbol
     * @return BetterReflector
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
     * @return BetterReflector
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
     * @return BetterReflector|null
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
     * @return BetterReflector[]
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
     * @return BetterReflector[]
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
                    if ($symbol->getType() == Symbol::SYMBOL_CLASS) {
                        $reflections[] = $this->reflectNode($node, null, $filename);
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
     * @return BetterReflector[]
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
     * @return Reflection\BetterReflector[]
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
