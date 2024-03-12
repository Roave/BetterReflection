<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection;

use PhpParser\Node;
use PhpParser\Node\Expr\Throw_ as NodeThrow;
use PhpParser\Node\Expr\Yield_ as YieldNode;
use PhpParser\Node\Expr\YieldFrom as YieldFromNode;
use PhpParser\Node\Stmt\ClassMethod as MethodNode;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\FindingVisitor;
use Roave\BetterReflection\Reflection\Annotation\AnnotationHelper;
use Roave\BetterReflection\Reflection\Attribute\ReflectionAttributeHelper;
use Roave\BetterReflection\Reflection\Exception\CodeLocationMissing;
use Roave\BetterReflection\SourceLocator\Located\LocatedSource;
use Roave\BetterReflection\Util\CalculateReflectionColumn;
use Roave\BetterReflection\Util\Exception\NoNodePosition;
use Roave\BetterReflection\Util\GetLastDocComment;

use function array_filter;
use function array_values;
use function assert;
use function count;
use function is_array;

/** @psalm-immutable */
trait ReflectionFunctionAbstract
{
    /**
     * @var non-empty-string
     * @psalm-allow-private-mutation
     */
    private string $name;

    /**
     * @var array<non-empty-string, ReflectionParameter>
     * @psalm-allow-private-mutation
     */
    private array $parameters;

    /** @psalm-allow-private-mutation */
    private bool $returnsReference;

    /** @psalm-allow-private-mutation */
    private ReflectionNamedType|ReflectionUnionType|ReflectionIntersectionType|null $returnType;

    /**
     * @var list<ReflectionAttribute>
     * @psalm-allow-private-mutation
     */
    private array $attributes;

    /**
     * @var non-empty-string|null
     * @psalm-allow-private-mutation
     */
    private string|null $docComment;

    /**
     * @var positive-int|null
     * @psalm-allow-private-mutation
     */
    private int|null $startLine;

    /**
     * @var positive-int|null
     * @psalm-allow-private-mutation
     */
    private int|null $endLine;

    /**
     * @var positive-int|null
     * @psalm-allow-private-mutation
     */
    private int|null $startColumn;

    /**
     * @var positive-int|null
     * @psalm-allow-private-mutation
     */
    private int|null $endColumn;

    /** @psalm-allow-private-mutation */
    private bool $couldThrow = false;

    /** @psalm-allow-private-mutation */
    private bool $isClosure = false;
    /** @psalm-allow-private-mutation */
    private bool $isGenerator = false;

    /** @return non-empty-string */
    abstract public function __toString(): string;

    /** @return non-empty-string */
    abstract public function getShortName(): string;

    /** @psalm-external-mutation-free */
    private function fillFromNode(MethodNode|Node\Stmt\Function_|Node\Expr\Closure|Node\Expr\ArrowFunction $node): void
    {
        $this->parameters       = $this->createParameters($node);
        $this->returnsReference = $node->returnsByRef();
        $this->returnType       = $this->createReturnType($node);
        $this->attributes       = ReflectionAttributeHelper::createAttributes($this->reflector, $this, $node->attrGroups);
        $this->docComment       = GetLastDocComment::forNode($node);
        $this->couldThrow       = $this->computeCouldThrow($node);

        $startLine = null;
        if ($node->hasAttribute('startLine')) {
            $startLine = $node->getStartLine();
            assert($startLine > 0);
        }

        $endLine = null;
        if ($node->hasAttribute('endLine')) {
            $endLine = $node->getEndLine();
            assert($endLine > 0);
        }

        $this->startLine = $startLine;
        $this->endLine   = $endLine;

        try {
            $this->startColumn = CalculateReflectionColumn::getStartColumn($this->getLocatedSource()->getSource(), $node);
        } catch (NoNodePosition) {
            $this->startColumn = null;
        }

        try {
            $this->endColumn = CalculateReflectionColumn::getEndColumn($this->getLocatedSource()->getSource(), $node);
        } catch (NoNodePosition) {
            $this->endColumn = null;
        }
    }

    /** @return array<non-empty-string, ReflectionParameter> */
    private function createParameters(Node\Stmt\ClassMethod|Node\Stmt\Function_|Node\Expr\Closure|Node\Expr\ArrowFunction $node): array
    {
        $parameters = [];

        /** @var list<Node\Param> $nodeParams */
        $nodeParams = $node->params;
        foreach ($nodeParams as $paramIndex => $paramNode) {
            $parameter = ReflectionParameter::createFromNode(
                $this->reflector,
                $paramNode,
                $this,
                $paramIndex,
                $this->isParameterOptional($nodeParams, $paramIndex),
            );

            $parameters[$parameter->getName()] = $parameter;
        }

        return $parameters;
    }

    /**
     * Get the "full" name of the function (e.g. for A\B\foo, this will return
     * "A\B\foo").
     *
     * @return non-empty-string
     */
    public function getName(): string
    {
        $namespace = $this->getNamespaceName();

        if ($namespace === null) {
            return $this->getShortName();
        }

        return $namespace . '\\' . $this->getShortName();
    }

    /**
     * Get the "namespace" name of the function (e.g. for A\B\foo, this will
     * return "A\B").
     */
    public function getNamespaceName(): string|null
    {
        return $this->namespace;
    }

    /**
     * Decide if this function is part of a namespace. Returns false if the class
     * is in the global namespace or does not have a specified namespace.
     */
    public function inNamespace(): bool
    {
        return $this->namespace !== null;
    }

    /**
     * Get the number of parameters for this class.
     *
     * @return positive-int|0
     */
    public function getNumberOfParameters(): int
    {
        return count($this->parameters);
    }

    /**
     * Get the number of required parameters for this method.
     *
     * @return positive-int|0
     */
    public function getNumberOfRequiredParameters(): int
    {
        return count(array_filter(
            $this->parameters,
            static fn (ReflectionParameter $p): bool => ! $p->isOptional(),
        ));
    }

    /**
     * Get an array list of the parameters for this method signature, as an
     * array of ReflectionParameter instances.
     *
     * @return list<ReflectionParameter>
     */
    public function getParameters(): array
    {
        return array_values($this->parameters);
    }

    /** @param list<Node\Param> $parameterNodes */
    private function isParameterOptional(array $parameterNodes, int $parameterIndex): bool
    {
        foreach ($parameterNodes as $otherParameterIndex => $otherParameterNode) {
            if ($otherParameterIndex < $parameterIndex) {
                continue;
            }

            // When we find next parameter that does not have a default or is not variadic,
            // it means current parameter cannot be optional EVEN if it has a default value
            if ($otherParameterNode->default === null && ! $otherParameterNode->variadic) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get a single parameter by name. Returns null if parameter not found for
     * the function.
     *
     * @param non-empty-string $parameterName
     */
    public function getParameter(string $parameterName): ReflectionParameter|null
    {
        return $this->parameters[$parameterName] ?? null;
    }

    /** @return non-empty-string|null */
    public function getDocComment(): string|null
    {
        return $this->docComment;
    }

    /** @return non-empty-string|null */
    public function getFileName(): string|null
    {
        return $this->locatedSource->getFileName();
    }

    public function getLocatedSource(): LocatedSource
    {
        return $this->locatedSource;
    }

    /**
     * Is this function a closure?
     */
    public function isClosure(): bool
    {
        return $this->isClosure;
    }

    public function isDeprecated(): bool
    {
        return AnnotationHelper::isDeprecated($this->docComment);
    }

    public function isInternal(): bool
    {
        return $this->locatedSource->isInternal();
    }

    /**
     * Is this a user-defined function (will always return the opposite of
     * whatever isInternal returns).
     */
    public function isUserDefined(): bool
    {
        return ! $this->isInternal();
    }

    /** @return non-empty-string|null */
    public function getExtensionName(): string|null
    {
        return $this->locatedSource->getExtensionName();
    }

    /**
     * Check if the function has a variadic parameter.
     */
    public function isVariadic(): bool
    {
        foreach ($this->parameters as $parameter) {
            if ($parameter->isVariadic()) {
                return true;
            }
        }

        return false;
    }

    /** Checks if the function/method contains `throw` expressions. */
    public function couldThrow(): bool
    {
        return $this->couldThrow;
    }

    private function computeCouldThrow(MethodNode|Node\Stmt\Function_|Node\Expr\Closure|Node\Expr\ArrowFunction $node): bool
    {
        $statements = $node->getStmts();

        if ($statements === null) {
            return false;
        }

        $visitor   = new FindingVisitor(static fn (Node $node): bool => $node instanceof NodeThrow);
        $traverser = new NodeTraverser($visitor);
        $traverser->traverse($statements);

        return $visitor->getFoundNodes() !== [];
    }

    /**
     * Recursively search an array of statements (PhpParser nodes) to find if a
     * yield expression exists anywhere (thus indicating this is a generator).
     */
    private function nodeIsOrContainsYield(Node $node): bool
    {
        if ($node instanceof YieldNode) {
            return true;
        }

        if ($node instanceof YieldFromNode) {
            return true;
        }

        /** @psalm-var string $nodeName */
        foreach ($node->getSubNodeNames() as $nodeName) {
            $nodeProperty = $node->$nodeName;

            if ($nodeProperty instanceof Node && $this->nodeIsOrContainsYield($nodeProperty)) {
                return true;
            }

            if (! is_array($nodeProperty)) {
                continue;
            }

            /** @psalm-var mixed $nodePropertyArrayItem */
            foreach ($nodeProperty as $nodePropertyArrayItem) {
                if ($nodePropertyArrayItem instanceof Node && $this->nodeIsOrContainsYield($nodePropertyArrayItem)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if this function can be used as a generator (i.e. contains the
     * "yield" keyword).
     */
    public function isGenerator(): bool
    {
        return $this->isGenerator;
    }

    /**
     * Get the line number that this function starts on.
     *
     * @return positive-int
     *
     * @throws CodeLocationMissing
     */
    public function getStartLine(): int
    {
        if ($this->startLine === null) {
            throw CodeLocationMissing::create();
        }

        return $this->startLine;
    }

    /**
     * Get the line number that this function ends on.
     *
     * @return positive-int
     *
     * @throws CodeLocationMissing
     */
    public function getEndLine(): int
    {
        if ($this->endLine === null) {
            throw CodeLocationMissing::create();
        }

        return $this->endLine;
    }

    /**
     * @return positive-int
     *
     * @throws CodeLocationMissing
     */
    public function getStartColumn(): int
    {
        if ($this->startColumn === null) {
            throw CodeLocationMissing::create();
        }

        return $this->startColumn;
    }

    /**
     * @return positive-int
     *
     * @throws CodeLocationMissing
     */
    public function getEndColumn(): int
    {
        if ($this->endColumn === null) {
            throw CodeLocationMissing::create();
        }

        return $this->endColumn;
    }

    /**
     * Is this function declared as a reference.
     */
    public function returnsReference(): bool
    {
        return $this->returnsReference;
    }

    /**
     * Get the return type declaration
     */
    public function getReturnType(): ReflectionNamedType|ReflectionUnionType|ReflectionIntersectionType|null
    {
        if ($this->hasTentativeReturnType()) {
            return null;
        }

        return $this->returnType;
    }

    /**
     * Do we have a return type declaration
     */
    public function hasReturnType(): bool
    {
        if ($this->hasTentativeReturnType()) {
            return false;
        }

        return $this->returnType !== null;
    }

    public function hasTentativeReturnType(): bool
    {
        if ($this->isUserDefined()) {
            return false;
        }

        return AnnotationHelper::hasTentativeReturnType($this->docComment);
    }

    public function getTentativeReturnType(): ReflectionNamedType|ReflectionUnionType|ReflectionIntersectionType|null
    {
        if (! $this->hasTentativeReturnType()) {
            return null;
        }

        return $this->returnType;
    }

    private function createReturnType(MethodNode|Node\Stmt\Function_|Node\Expr\Closure|Node\Expr\ArrowFunction $node): ReflectionNamedType|ReflectionUnionType|ReflectionIntersectionType|null
    {
        $returnType = $node->getReturnType();

        if ($returnType === null) {
            return null;
        }

        assert($returnType instanceof Node\Identifier || $returnType instanceof Node\Name || $returnType instanceof Node\NullableType || $returnType instanceof Node\UnionType || $returnType instanceof Node\IntersectionType);

        return ReflectionType::createFromNode($this->reflector, $this, $returnType);
    }

    /** @return list<ReflectionAttribute> */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /** @return list<ReflectionAttribute> */
    public function getAttributesByName(string $name): array
    {
        return ReflectionAttributeHelper::filterAttributesByName($this->getAttributes(), $name);
    }

    /**
     * @param class-string $className
     *
     * @return list<ReflectionAttribute>
     */
    public function getAttributesByInstance(string $className): array
    {
        return ReflectionAttributeHelper::filterAttributesByInstance($this->getAttributes(), $className);
    }
}
