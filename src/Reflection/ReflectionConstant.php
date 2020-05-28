<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection;

use PhpParser\Node;
use PhpParser\Node\Stmt\Namespace_ as NamespaceNode;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\NodeCompiler\CompileNodeToValue;
use Roave\BetterReflection\NodeCompiler\CompilerContext;
use Roave\BetterReflection\Reflection\Exception\InvalidConstantNode;
use Roave\BetterReflection\Reflection\StringCast\ReflectionConstantStringCast;
use Roave\BetterReflection\Reflector\Exception\IdentifierNotFound;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\Located\LocatedSource;
use Roave\BetterReflection\Util\CalculateReflectionColum;
use Roave\BetterReflection\Util\ConstantNodeChecker;
use Roave\BetterReflection\Util\GetFirstDocComment;
use function array_slice;
use function assert;
use function count;
use function explode;
use function implode;
use function substr_count;

class ReflectionConstant implements Reflection
{
    /** @var Reflector */
    private $reflector;

    /** @var Node\Stmt\Const_|Node\Expr\FuncCall */
    private $node;

    /** @var LocatedSource */
    private $locatedSource;

    /** @var NamespaceNode|null */
    private $declaringNamespace;

    /** @var int|null */
    private $positionInNode;

    /** @var scalar|array<scalar>|null const value */
    private $value;

    /** @var bool */
    private $valueWasCached = false;

    private function __construct()
    {
    }

    /**
     * Create a ReflectionConstant by name, using default reflectors etc.
     *
     * @throws IdentifierNotFound
     */
    public static function createFromName(string $constantName) : self
    {
        return (new BetterReflection())->constantReflector()->reflect($constantName);
    }

    /**
     * Create a reflection of a constant
     *
     * @internal
     *
     * @param Node\Stmt\Const_|Node\Expr\FuncCall $node Node has to be processed by the PhpParser\NodeVisitor\NameResolver
     */
    public static function createFromNode(
        Reflector $reflector,
        Node $node,
        LocatedSource $locatedSource,
        ?NamespaceNode $namespace = null,
        ?int $positionInNode = null
    ) : self {
        return $node instanceof Node\Stmt\Const_
            ? self::createFromConstKeyword($reflector, $node, $locatedSource, $namespace, $positionInNode)
            : self::createFromDefineFunctionCall($reflector, $node, $locatedSource);
    }

    private static function createFromConstKeyword(
        Reflector $reflector,
        Node\Stmt\Const_ $node,
        LocatedSource $locatedSource,
        ?NamespaceNode $namespace,
        int $positionInNode
    ) : self {
        $constant                     = new self();
        $constant->reflector          = $reflector;
        $constant->node               = $node;
        $constant->locatedSource      = $locatedSource;
        $constant->declaringNamespace = $namespace;
        $constant->positionInNode     = $positionInNode;

        return $constant;
    }

    /**
     * @throws InvalidConstantNode
     */
    private static function createFromDefineFunctionCall(
        Reflector $reflector,
        Node\Expr\FuncCall $node,
        LocatedSource $locatedSource
    ) : self {
        ConstantNodeChecker::assertValidDefineFunctionCall($node);

        $constant                = new self();
        $constant->reflector     = $reflector;
        $constant->node          = $node;
        $constant->locatedSource = $locatedSource;

        return $constant;
    }

    /**
     * Get the "short" name of the constant (e.g. for A\B\FOO, this will return
     * "FOO").
     */
    public function getShortName() : string
    {
        if ($this->node instanceof Node\Expr\FuncCall) {
            $nameParts = explode('\\', $this->getNameFromDefineFunctionCall($this->node));

            return $nameParts[count($nameParts) - 1];
        }

        /** @psalm-suppress PossiblyNullArrayOffset */
        return $this->node->consts[$this->positionInNode]->name->name;
    }

    /**
     * Get the "full" name of the constant (e.g. for A\B\FOO, this will return
     * "A\B\FOO").
     */
    public function getName() : string
    {
        if (! $this->inNamespace()) {
            return $this->getShortName();
        }

        if ($this->node instanceof Node\Expr\FuncCall) {
            return $this->getNameFromDefineFunctionCall($this->node);
        }

        /**
         * @psalm-suppress UndefinedPropertyFetch
         * @psalm-suppress PossiblyNullArrayOffset
         */
        return $this->node->consts[$this->positionInNode]->namespacedName->toString();
    }

    /**
     * Get the "namespace" name of the constant (e.g. for A\B\FOO, this will
     * return "A\B").
     *
     * @psalm-suppress PossiblyNullPropertyFetch
     */
    public function getNamespaceName() : string
    {
        if (! $this->inNamespace()) {
            return '';
        }

        $namespaceParts = $this->node instanceof Node\Expr\FuncCall
            ? array_slice(explode('\\', $this->getNameFromDefineFunctionCall($this->node)), 0, -1)
            : $this->declaringNamespace->name->parts;

        return implode('\\', $namespaceParts);
    }

    /**
     * Decide if this constant is part of a namespace. Returns false if the constant
     * is in the global namespace or does not have a specified namespace.
     */
    public function inNamespace() : bool
    {
        if ($this->node instanceof Node\Expr\FuncCall) {
            return substr_count($this->getNameFromDefineFunctionCall($this->node), '\\') !== 0;
        }

        return $this->declaringNamespace !== null
            && $this->declaringNamespace->name !== null;
    }

    public function getExtensionName() : ?string
    {
        return $this->locatedSource->getExtensionName();
    }

    /**
     * Is this an internal constant?
     */
    public function isInternal() : bool
    {
        return $this->locatedSource->isInternal();
    }

    /**
     * Is this a user-defined function (will always return the opposite of
     * whatever isInternal returns).
     */
    public function isUserDefined() : bool
    {
        return ! $this->isInternal();
    }

    /**
     * Returns constant value
     *
     * @return scalar|array<scalar>|null
     */
    public function getValue()
    {
        if ($this->valueWasCached !== false) {
            return $this->value;
        }

        /** @psalm-suppress PossiblyNullArrayOffset */
        $valueNode = $this->node instanceof Node\Expr\FuncCall
            ? $this->node->args[1]->value
            : $this->node->consts[$this->positionInNode]->value;

        /** @psalm-suppress UndefinedPropertyFetch */
        $this->value          = (new CompileNodeToValue())->__invoke(
            $valueNode,
            new CompilerContext($this->reflector, null)
        );
        $this->valueWasCached = true;

        return $this->value;
    }

    public function getFileName() : ?string
    {
        return $this->locatedSource->getFileName();
    }

    public function getLocatedSource() : LocatedSource
    {
        return $this->locatedSource;
    }

    /**
     * Get the line number that this constant starts on.
     */
    public function getStartLine() : int
    {
        return $this->node->getStartLine();
    }

    /**
     * Get the line number that this constant ends on.
     */
    public function getEndLine() : int
    {
        return $this->node->getEndLine();
    }

    public function getStartColumn() : int
    {
        return CalculateReflectionColum::getStartColumn($this->locatedSource->getSource(), $this->node);
    }

    public function getEndColumn() : int
    {
        return CalculateReflectionColum::getEndColumn($this->locatedSource->getSource(), $this->node);
    }

    /**
     * Returns the doc comment for this constant
     */
    public function getDocComment() : string
    {
        return GetFirstDocComment::forNode($this->node);
    }

    public function __toString() : string
    {
        return ReflectionConstantStringCast::toString($this);
    }

    /**
     * @return Node\Stmt\Const_|Node\Expr\FuncCall
     */
    public function getAst() : Node
    {
        return $this->node;
    }

    private function getNameFromDefineFunctionCall(Node\Expr\FuncCall $node) : string
    {
        $nameNode = $node->args[0]->value;
        assert($nameNode instanceof Node\Scalar\String_);

        return $nameNode->value;
    }
}
