<?php

declare(strict_types=1);

namespace Roave\BetterReflection\SourceLocator\Ast;

use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Namespace_;
use Roave\BetterReflection\Identifier\IdentifierType;
use Roave\BetterReflection\Reflection\Reflection;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\Ast\Strategy\AstConversionStrategy;
use Roave\BetterReflection\SourceLocator\Located\LocatedSource;
use function assert;

/**
 * @internal
 */
final class FindReflectionsInTree
{
    /** @var AstConversionStrategy */
    private $astConversionStrategy;

    public function __construct(AstConversionStrategy $astConversionStrategy)
    {
        $this->astConversionStrategy = $astConversionStrategy;
    }

    /**
     * Find all reflections of a given type in an Abstract Syntax Tree
     *
     * @param Node[] $ast
     *
     * @return Reflection[]
     */
    public function __invoke(
        Reflector $reflector,
        array $ast,
        IdentifierType $identifierType,
        LocatedSource $locatedSource
    ) : array {
        $nodeTraverser = new NodeTraverser();
        $nodeTraverser->addVisitor(new NameResolver());
        $ast = $nodeTraverser->traverse($ast);

        static $collectNamespaceOrphanSymbols;
        static $collectNamespacedSymbols;

        if ($collectNamespacedSymbols === null) {
            $collectNamespaceOrphanSymbols = [
                // do not enter Namespace_ but do collect it
                new CollectByTypeInstructions(Namespace_::CLASS, true, []),

                // collect ClassLike and keep searching in its stmts
                new CollectByTypeInstructions(ClassLike::CLASS, true, ['stmts']),

                // collect Function_ and keep searching in its stmts
                new CollectByTypeInstructions(Function_::CLASS, true, ['stmts']),

                // @TODO can add more instructions to blacklist bits of the AST where not to
                // look, for example constant expressions, function arguments and parameters,
                // etc.
            ];

            $collectNamespacedSymbols = [
                new CollectByTypeInstructions(ClassLike::CLASS, true, ['stmts']),
                new CollectByTypeInstructions(Function_::CLASS, true, ['stmts']),

                // @TODO same as above
            ];
        }

        $collectByType = new CollectByType();
        $rootNodes     = $collectByType->collect($collectNamespaceOrphanSymbols, [$ast]);
        /** @var Namespace_[]|ClassLike[]|Function_ $rootNodes */

        $reflections = [];
        foreach ($rootNodes as $node) {
            if ($node instanceof Namespace_) {
                $namespacedFunctionsAndClassLikes = $collectByType->collect($collectNamespacedSymbols, [$node->stmts]);
                foreach ($namespacedFunctionsAndClassLikes as $NSedNode) {
                    assert($NSedNode instanceof Function_ || $NSedNode instanceof ClassLike);
                    $useNamespace = $NSedNode instanceof Class_ && $NSedNode->isAnonymous() ? null : $node;
                    $reflection   = $this->astConversionStrategy->__invoke($reflector, $NSedNode, $locatedSource, $useNamespace);
                    if ($identifierType->isMatchingReflector($reflection)) {
                        $reflections[] = $reflection;
                    }
                }
                continue;
            }
            assert($node instanceof Function_ || $node instanceof ClassLike);
            $reflection = $this->astConversionStrategy->__invoke($reflector, $node, $locatedSource, null);
            if ($identifierType->isMatchingReflector($reflection)) {
                $reflections[] = $reflection;
            }
        }

        return $reflections;
    }
}
