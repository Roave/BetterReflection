<?php

declare(strict_types=1);

namespace Roave\BetterReflection\SourceLocator\Ast;

use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\NodeVisitorAbstract;
use Roave\BetterReflection\Identifier\IdentifierType;
use Roave\BetterReflection\Reflection\Exception\InvalidConstantNode;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionConstant;
use Roave\BetterReflection\Reflection\ReflectionFunction;
use Roave\BetterReflection\Reflector\Exception\IdentifierNotFound;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\Ast\Strategy\AstConversionStrategy;
use Roave\BetterReflection\SourceLocator\Located\LocatedSource;
use Roave\BetterReflection\Util\ConstantNodeChecker;

use function assert;
use function count;

/** @internal */
final class FindReflectionsInTree
{
    public function __construct(private AstConversionStrategy $astConversionStrategy)
    {
    }

    /**
     * Find all reflections of a given type in an Abstract Syntax Tree
     *
     * @param Node[] $ast
     *
     * @return list<ReflectionClass|ReflectionFunction|ReflectionConstant>
     */
    public function __invoke(
        Reflector $reflector,
        array $ast,
        IdentifierType $identifierType,
        LocatedSource $locatedSource,
    ): array {
        $nodeVisitor = new class ($reflector, $identifierType, $locatedSource, $this->astConversionStrategy) extends NodeVisitorAbstract
        {
            /** @var list<ReflectionClass|ReflectionFunction|ReflectionConstant> */
            private array $reflections = [];

            private Namespace_|null $currentNamespace = null;

            public function __construct(
                private Reflector $reflector,
                private IdentifierType $identifierType,
                private LocatedSource $locatedSource,
                private AstConversionStrategy $astConversionStrategy,
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

                if (
                    $this->identifierType->isClass()
                    && (
                        $node instanceof Node\Stmt\Class_
                        || $node instanceof Node\Stmt\Interface_
                        || $node instanceof Node\Stmt\Trait_
                        || $node instanceof Node\Stmt\Enum_
                    )
                ) {
                    $classNamespace = $node->name === null ? null : $this->currentNamespace;

                    /** @psalm-suppress InternalMethod */
                    $this->reflections[] = $this->astConversionStrategy->__invoke($this->reflector, $node, $this->locatedSource, $classNamespace);

                    return null;
                }

                if ($this->identifierType->isConstant()) {
                    if ($node instanceof Node\Stmt\Const_) {
                        for ($i = 0; $i < count($node->consts); $i++) {
                            /** @psalm-suppress InternalMethod */
                            $this->reflections[] = $this->astConversionStrategy->__invoke($this->reflector, $node, $this->locatedSource, $this->currentNamespace, $i);
                        }

                        return null;
                    }

                    if ($node instanceof Node\Expr\FuncCall) {
                        try {
                            /** @psalm-suppress InternalClass, InternalMethod */
                            ConstantNodeChecker::assertValidDefineFunctionCall($node);
                        } catch (InvalidConstantNode) {
                            return null;
                        }

                        if ($node->name->hasAttribute('namespacedName')) {
                            $namespacedName = $node->name->getAttribute('namespacedName');
                            assert($namespacedName instanceof Name);
                            if (count($namespacedName->parts) > 1) {
                                try {
                                    $this->reflector->reflectFunction($namespacedName->toString());

                                    return null;
                                } catch (IdentifierNotFound) {
                                    // Global define()
                                }
                            }
                        }

                        /** @psalm-suppress InternalMethod */
                        $this->reflections[] = $this->astConversionStrategy->__invoke($this->reflector, $node, $this->locatedSource, $this->currentNamespace);

                        return null;
                    }
                }

                if ($this->identifierType->isFunction() && $node instanceof Node\Stmt\Function_) {
                    /** @psalm-suppress InternalMethod */
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

            /** @return list<ReflectionClass|ReflectionFunction|ReflectionConstant> */
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
