<?php
declare(strict_types=1);

namespace Roave\BetterReflection\SourceLocator\Ast;

use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use Roave\BetterReflection\Identifier\IdentifierType;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\Ast\Strategy\AstConversionStrategy;
use Roave\BetterReflection\SourceLocator\Located\LocatedSource;
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
    public function __invoke(
        Reflector $reflector,
        array $ast,
        IdentifierType $identifierType,
        LocatedSource $locatedSource
    ) : array {
        $reflections = [];

        $nodeVisitor = new class($reflections, $reflector, $identifierType, $locatedSource, $this->astConversionStrategy) extends NodeVisitorAbstract
        {
            /**
             * @var \Roave\BetterReflection\Reflection\Reflection[]
             */
            private $reflections;

            /**
             * @var \Roave\BetterReflection\Reflector\Reflector
             */
            private $reflector;

            /**
             * @var \Roave\BetterReflection\Identifier\IdentifierType
             */
            private $identifierType;

            /**
             * @var \Roave\BetterReflection\SourceLocator\Located\LocatedSource
             */
            private $locatedSource;

            /**
             * @var \Roave\BetterReflection\SourceLocator\Ast\Strategy\AstConversionStrategy
             */
            private $astConversionStrategy;

            /**
             * @var Node\Stmt\Namespace_|null
             */
            private $currentNamespace;

            public function __construct(
                array &$reflections,
                Reflector $reflector,
                IdentifierType $identifierType,
                LocatedSource $locatedSource,
                AstConversionStrategy $astConversionStrategy
            )
            {
                $this->reflections = &$reflections;
                $this->reflector = $reflector;
                $this->identifierType = $identifierType;
                $this->locatedSource = $locatedSource;
                $this->astConversionStrategy = $astConversionStrategy;
            }

            public function enterNode(Node $node)
            {
                if ($node instanceof Node\Stmt\Namespace_) {
                    $this->currentNamespace = $node;
                } elseif ($node instanceof Node\Stmt\ClassLike) {
                    $reflection = $this->astConversionStrategy->__invoke($this->reflector, $node, $this->locatedSource, $this->currentNamespace);
                    if ($this->identifierType->isMatchingReflector($reflection)) {
                        $this->reflections[] = $reflection;
                    }
                } elseif ($node instanceof Node\Stmt\Function_) {
                    $reflection = $this->astConversionStrategy->__invoke($this->reflector, $node, $this->locatedSource, $this->currentNamespace);
                    if ($this->identifierType->isMatchingReflector($reflection)) {
                        $this->reflections[] = $reflection;
                    }
                }
            }

            public function leaveNode(Node $node)
            {
                if ($node instanceof Node\Stmt\Namespace_) {
                    $this->currentNamespace = null;
                }
            }
        };

        $nodeTraverser = new NodeTraverser();
        $nodeTraverser->addVisitor($nodeVisitor);
        $nodeTraverser->traverse($ast);

        return $reflections;
    }
}
