<?php

namespace Roave\BetterReflection\SourceLocator\Ast\Strategy;

use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\Located\LocatedSource;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionFunction;
use Roave\BetterReflection\Reflection\Reflection;
use PhpParser\Node;
use Roave\BetterReflection\Context\CachedContextFactory;

/**
 * @internal
 */
class NodeToReflection implements AstConversionStrategy
{
    /**
     * @var ContextFactory
     */
    private $contextFactory;
    
    public function __construct(ContextFactory $contextFactory = null)
    {
        $this->contextFactory = $contextFactory ?: new CachedContextFactory();
    }

    /**
     * Take an AST node in some located source (potentially in a namespace) and
     * convert it to a Reflection
     *
     * @param Reflector $reflector
     * @param Node $node
     * @param LocatedSource $locatedSource
     * @param Node\Stmt\Namespace_|null $namespace
     * @return Reflection|null
     */
    public function __invoke(Reflector $reflector, Node $node, LocatedSource $locatedSource, Node\Stmt\Namespace_ $namespace = null)
    {
        $context = $this->contextFactory->createForNamespace(
            $namespace ? (string) $namespace->name : null,
            $locatedSource->getSource()
        );

        if ($node instanceof Node\Stmt\ClassLike) {

            return ReflectionClass::createFromNode(
                $reflector,
                $node,
                $locatedSource,
                $context
            );
        }

        if ($node instanceof Node\FunctionLike) {
            return ReflectionFunction::createFromNode(
                $reflector,
                $node,
                $locatedSource,
                $context
            );
        }

        return null;
    }
}
