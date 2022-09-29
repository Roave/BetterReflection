<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection;

use PhpParser\Node;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\NodeCompiler\CompiledValue;
use Roave\BetterReflection\NodeCompiler\CompileNodeToValue;
use Roave\BetterReflection\NodeCompiler\CompilerContext;
use Roave\BetterReflection\Reflection\Annotation\AnnotationHelper;
use Roave\BetterReflection\Reflection\Exception\InvalidConstantNode;
use Roave\BetterReflection\Reflection\StringCast\ReflectionConstantStringCast;
use Roave\BetterReflection\Reflector\Exception\IdentifierNotFound;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\Located\LocatedSource;
use Roave\BetterReflection\Util\CalculateReflectionColumn;
use Roave\BetterReflection\Util\ConstantNodeChecker;
use Roave\BetterReflection\Util\GetLastDocComment;

use function array_slice;
use function assert;
use function count;
use function explode;
use function implode;
use function is_int;

class ReflectionConstant implements Reflection
{
    /** @var non-empty-string */
    private string $name;

    /** @var non-empty-string */
    private string $shortName;

    private Node\Expr $value;

    private string|null $docComment;

    /** @var positive-int */
    private int $startLine;

    /** @var positive-int */
    private int $endLine;

    /** @var positive-int */
    private int $startColumn;

    /** @var positive-int */
    private int $endColumn;

    private CompiledValue|null $compiledValue = null;

    private function __construct(
        private Reflector $reflector,
        Node\Stmt\Const_|Node\Expr\FuncCall $node,
        private LocatedSource $locatedSource,
        private string|null $namespace = null,
        int|null $positionInNode = null,
    ) {
        $this->setNamesFromNode($node, $positionInNode);

        if ($node instanceof Node\Expr\FuncCall) {
            $argumentValueNode = $node->args[1];
            assert($argumentValueNode instanceof Node\Arg);
            $this->value = $argumentValueNode->value;
        } else {
            /** @psalm-suppress PossiblyNullArrayOffset */
            $this->value = $node->consts[$positionInNode]->value;
        }

        $this->docComment = GetLastDocComment::forNode($node);

        $startLine = $node->getStartLine();
        assert($startLine > 0);
        $endLine = $node->getEndLine();
        assert($endLine > 0);

        $this->startLine   = $startLine;
        $this->endLine     = $endLine;
        $this->startColumn = CalculateReflectionColumn::getStartColumn($this->locatedSource->getSource(), $node);
        $this->endColumn   = CalculateReflectionColumn::getEndColumn($this->locatedSource->getSource(), $node);
    }

    /**
     * Create a ReflectionConstant by name, using default reflectors etc.
     *
     * @throws IdentifierNotFound
     */
    public static function createFromName(string $constantName): self
    {
        return (new BetterReflection())->reflector()->reflectConstant($constantName);
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
        string|null $namespace = null,
        int|null $positionInNode = null,
    ): self {
        if ($node instanceof Node\Stmt\Const_) {
            assert(is_int($positionInNode));

            return self::createFromConstKeyword($reflector, $node, $locatedSource, $namespace, $positionInNode);
        }

        return self::createFromDefineFunctionCall($reflector, $node, $locatedSource);
    }

    private static function createFromConstKeyword(
        Reflector $reflector,
        Node\Stmt\Const_ $node,
        LocatedSource $locatedSource,
        string|null $namespace,
        int $positionInNode,
    ): self {
        return new self(
            $reflector,
            $node,
            $locatedSource,
            $namespace,
            $positionInNode,
        );
    }

    /** @throws InvalidConstantNode */
    private static function createFromDefineFunctionCall(
        Reflector $reflector,
        Node\Expr\FuncCall $node,
        LocatedSource $locatedSource,
    ): self {
        ConstantNodeChecker::assertValidDefineFunctionCall($node);

        return new self(
            $reflector,
            $node,
            $locatedSource,
        );
    }

    /**
     * Get the "short" name of the constant (e.g. for A\B\FOO, this will return
     * "FOO").
     *
     * @return non-empty-string
     */
    public function getShortName(): string
    {
        return $this->shortName;
    }

    /**
     * Get the "full" name of the constant (e.g. for A\B\FOO, this will return
     * "A\B\FOO").
     *
     * @return non-empty-string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the "namespace" name of the constant (e.g. for A\B\FOO, this will
     * return "A\B").
     */
    public function getNamespaceName(): string|null
    {
        return $this->namespace;
    }

    /**
     * Decide if this constant is part of a namespace. Returns false if the constant
     * is in the global namespace or does not have a specified namespace.
     */
    public function inNamespace(): bool
    {
        return $this->namespace !== null;
    }

    public function getExtensionName(): string|null
    {
        return $this->locatedSource->getExtensionName();
    }

    /**
     * Is this an internal constant?
     */
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

    public function isDeprecated(): bool
    {
        return AnnotationHelper::isDeprecated($this->getDocComment());
    }

    public function getValueExpression(): Node\Expr
    {
        return $this->value;
    }

    /**
     * Returns constant value
     */
    public function getValue(): mixed
    {
        if ($this->compiledValue === null) {
            $this->compiledValue = (new CompileNodeToValue())->__invoke(
                $this->value,
                new CompilerContext($this->reflector, $this),
            );
        }

        return $this->compiledValue->value;
    }

    public function getFileName(): string|null
    {
        return $this->locatedSource->getFileName();
    }

    public function getLocatedSource(): LocatedSource
    {
        return $this->locatedSource;
    }

    /**
     * Get the line number that this constant starts on.
     *
     * @return positive-int
     */
    public function getStartLine(): int
    {
        return $this->startLine;
    }

    /**
     * Get the line number that this constant ends on.
     *
     * @return positive-int
     */
    public function getEndLine(): int
    {
        return $this->endLine;
    }

    /** @return positive-int */
    public function getStartColumn(): int
    {
        return $this->startColumn;
    }

    /** @return positive-int */
    public function getEndColumn(): int
    {
        return $this->endColumn;
    }

    /**
     * Returns the doc comment for this constant
     */
    public function getDocComment(): string|null
    {
        return $this->docComment;
    }

    public function __toString(): string
    {
        return ReflectionConstantStringCast::toString($this);
    }

    private function setNamesFromNode(Node\Stmt\Const_|Node\Expr\FuncCall $node, int|null $positionInNode): void
    {
        if ($node instanceof Node\Expr\FuncCall) {
            $name = $this->getNameFromDefineFunctionCall($node);

            $nameParts       = explode('\\', $name);
            $this->namespace = implode('\\', array_slice($nameParts, 0, -1)) ?: null;

            $shortName = $nameParts[count($nameParts) - 1];
            assert($shortName !== '');
        } else {
            /** @psalm-suppress PossiblyNullArrayOffset */
            $constNode      = $node->consts[$positionInNode];
            $namespacedName = $constNode->namespacedName;
            assert($namespacedName instanceof Node\Name);

            $name = $namespacedName->toString();
            assert($name !== '');
            $shortName = $constNode->name->name;
            assert($shortName !== '');
        }

        $this->name      = $name;
        $this->shortName = $shortName;
    }

    /** @return non-empty-string */
    private function getNameFromDefineFunctionCall(Node\Expr\FuncCall $node): string
    {
        $argumentNameNode = $node->args[0];
        assert($argumentNameNode instanceof Node\Arg);
        $nameNode = $argumentNameNode->value;
        assert($nameNode instanceof Node\Scalar\String_);

        /** @psalm-var non-empty-string */
        return $nameNode->value;
    }
}
