<?php

declare(strict_types=1);

namespace Roave\BetterReflection\SourceLocator\Ast;

use Closure;
use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\NodeVisitorAbstract;
use Roave\BetterReflection\Identifier\IdentifierType;
use Roave\BetterReflection\Reflection\Exception\InvalidConstantNode;
use Roave\BetterReflection\Reflection\Reflection;
use Roave\BetterReflection\Reflector\Exception\IdentifierNotFound;
use Roave\BetterReflection\Reflector\FunctionReflector;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\Ast\Strategy\AstConversionStrategy;
use Roave\BetterReflection\SourceLocator\Located\LocatedSource;
use Roave\BetterReflection\Util\ConstantNodeChecker;

use function assert;
use function count;

/**
 * @internal
 */
final class FindReflectionsInTree
{
    private FunctionReflector $functionReflector;

    /**
     * @param Closure(): FunctionReflector $functionReflectorGetter
     */
    public function __construct(private AstConversionStrategy $astConversionStrategy, private Closure $functionReflectorGetter)
    {
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
        LocatedSource $locatedSource,
    ): array {
        $nodeVisitor = new class ($reflector, $identifierType, $locatedSource, $this->astConversionStrategy, $this->functionReflectorGetter->__invoke()) extends NodeVisitorAbstract
        {
            /** @var Reflection[] */
            private array $reflections = [];

            private ?Namespace_ $currentNamespace = null;

            public function __construct(
                private Reflector $reflector,
                private IdentifierType $identifierType,
                private LocatedSource $locatedSource,
                private AstConversionStrategy $astConversionStrategy,
                private FunctionReflector $functionReflector,
            ) {
            }

            /**
             * {@inheritDoc}
             */
            public function enterNode(Node $node)
            {
                if ($node instanceof Namespace_) {
                    $this->currentNamespace = $node;

                    return null;
                }

                if ($this->identifierType->isClass() && $node instanceof Node\Stmt\ClassLike) {
                    $classNamespace      = $node->name === null ? null : $this->currentNamespace;
                    $this->reflections[] = $this->astConversionStrategy->__invoke($this->reflector, $node, $this->locatedSource, $classNamespace);

                    return null;
                }

                if ($this->identifierType->isConstant()) {
                    if ($node instanceof Node\Stmt\Const_) {
                        for ($i = 0; $i < count($node->consts); $i++) {
                            $this->reflections[] = $this->astConversionStrategy->__invoke($this->reflector, $node, $this->locatedSource, $this->currentNamespace, $i);
                        }

                        return null;
                    }

                    if ($node instanceof Node\Expr\FuncCall) {
                        try {
                            ConstantNodeChecker::assertValidDefineFunctionCall($node);
                        } catch (InvalidConstantNode) {
                            return null;
                        }

                        if ($node->name->hasAttribute('namespacedName')) {
                            $namespacedName = $node->name->getAttribute('namespacedName');
                            assert($namespacedName instanceof Name);
                            if (count($namespacedName->parts) > 1) {
                                try {
                                    $this->functionReflector->reflect($namespacedName->toString());

                                    return null;
                                } catch (IdentifierNotFound) {
                                    // Global define()
                                }
                            }
                        }

                        $reflection = $this->astConversionStrategy->__invoke($this->reflector, $node, $this->locatedSource, $this->currentNamespace);

                        if ($this->identifierType->isMatchingReflector($reflection)) {
                            $this->reflections[] = $reflection;
                        }

                        return null;
                    }
                }

                if ($this->identifierType->isFunction() && $node instanceof Node\Stmt\Function_) {
                    $this->reflections[] = $this->astConversionStrategy->__invoke($this->reflector, $node, $this->locatedSource, $this->currentNamespace);
                }

                return null;
            }

            /**
             * {@inheritDoc}
             */
            public function leaveNode(Node $node)
            {
                if (! ($node instanceof Namespace_)) {
                    return null;
                }

                $this->currentNamespace = null;

                return null;
            }

            /**
             * @return Reflection[]
             */
            public function getReflections(): array
            {
                return $this->reflections;
            }
        };

        $nodeTraverser = new NodeTraverser();
        $nodeTraverser->addVisitor(new NameResolver());
        $nodeTraverser->addVisitor($nodeVisitor);
        $nodeTraverser->traverse($ast);

        return $nodeVisitor->getReflections();
    }
}
