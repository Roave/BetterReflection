<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassConst;
use ReflectionClassConstant as CoreReflectionClassConstant;
use Roave\BetterReflection\NodeCompiler\CompiledValue;
use Roave\BetterReflection\NodeCompiler\CompileNodeToValue;
use Roave\BetterReflection\NodeCompiler\CompilerContext;
use Roave\BetterReflection\Reflection\Annotation\AnnotationHelper;
use Roave\BetterReflection\Reflection\Attribute\ReflectionAttributeHelper;
use Roave\BetterReflection\Reflection\StringCast\ReflectionClassConstantStringCast;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\Util\CalculateReflectionColumn;
use Roave\BetterReflection\Util\GetLastDocComment;

use function assert;

class ReflectionClassConstant
{
    public const IS_FINAL = 32;

    /** @var non-empty-string */
    private string $name;

    private int $modifiers;

    private Node\Expr $value;

    private string|null $docComment;

    /** @var list<ReflectionAttribute> */
    private array $attributes;

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
        ClassConst $node,
        int $positionInNode,
        private ReflectionClass $declaringClass,
        private ReflectionClass $implementingClass,
    ) {
        $name = $node->consts[$positionInNode]->name->name;
        assert($name !== '');

        $this->name      = $name;
        $this->modifiers = $this->computeModifiers($node);
        $this->value     = $node->consts[$positionInNode]->value;

        $this->docComment = GetLastDocComment::forNode($node);
        $this->attributes = ReflectionAttributeHelper::createAttributes($reflector, $this, $node->attrGroups);

        $startLine = $node->getStartLine();
        assert($startLine > 0);
        $endLine = $node->getEndLine();
        assert($endLine > 0);

        $this->startLine   = $startLine;
        $this->endLine     = $endLine;
        $this->startColumn = CalculateReflectionColumn::getStartColumn($declaringClass->getLocatedSource()->getSource(), $node);
        $this->endColumn   = CalculateReflectionColumn::getEndColumn($declaringClass->getLocatedSource()->getSource(), $node);
    }

    /**
     * Create a reflection of a class's constant by Const Node
     *
     * @internal
     */
    public static function createFromNode(
        Reflector $reflector,
        ClassConst $node,
        int $positionInNode,
        ReflectionClass $declaringClass,
        ReflectionClass $implementingClass,
    ): self {
        return new self(
            $reflector,
            $node,
            $positionInNode,
            $declaringClass,
            $implementingClass,
        );
    }

    /** @internal */
    public static function withImplementingClass(self $classConstant, ReflectionClass $implementingClass): self
    {
        $clone                    = clone $classConstant;
        $clone->implementingClass = $implementingClass;

        return $clone;
    }

    /**
     * Get the name of the reflection (e.g. if this is a ReflectionClass this
     * will be the class name).
     *
     * @return non-empty-string
     */
    public function getName(): string
    {
        return $this->name;
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

    /**
     * Constant is public
     */
    public function isPublic(): bool
    {
        return ($this->modifiers & CoreReflectionClassConstant::IS_PUBLIC) === CoreReflectionClassConstant::IS_PUBLIC;
    }

    /**
     * Constant is private
     */
    public function isPrivate(): bool
    {
        // Private constant cannot be final
        return $this->modifiers === CoreReflectionClassConstant::IS_PRIVATE;
    }

    /**
     * Constant is protected
     */
    public function isProtected(): bool
    {
        return ($this->modifiers & CoreReflectionClassConstant::IS_PROTECTED) === CoreReflectionClassConstant::IS_PROTECTED;
    }

    public function isFinal(): bool
    {
        return ($this->modifiers & self::IS_FINAL) === self::IS_FINAL;
    }

    /**
     * Returns a bitfield of the access modifiers for this constant
     */
    public function getModifiers(): int
    {
        return $this->modifiers;
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
     * Get the declaring class
     */
    public function getDeclaringClass(): ReflectionClass
    {
        return $this->declaringClass;
    }

    /**
     * Get the class that implemented the method based on trait use.
     */
    public function getImplementingClass(): ReflectionClass
    {
        return $this->implementingClass;
    }

    /**
     * Returns the doc comment for this constant
     */
    public function getDocComment(): string|null
    {
        return $this->docComment;
    }

    public function isDeprecated(): bool
    {
        return AnnotationHelper::isDeprecated($this->getDocComment());
    }

    public function __toString(): string
    {
        return ReflectionClassConstantStringCast::toString($this);
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

    private function computeModifiers(ClassConst $node): int
    {
        $modifiers = $node->isFinal() ? self::IS_FINAL : 0;

        if ($node->isPrivate()) {
            // No += because private constant cannot be final
            $modifiers = CoreReflectionClassConstant::IS_PRIVATE;
        } elseif ($node->isProtected()) {
            $modifiers += CoreReflectionClassConstant::IS_PROTECTED;
        } else {
            $modifiers += CoreReflectionClassConstant::IS_PUBLIC;
        }

        return $modifiers;
    }
}
