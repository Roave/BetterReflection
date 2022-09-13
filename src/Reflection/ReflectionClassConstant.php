<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection;

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

class ReflectionClassConstant
{
    public const IS_FINAL = 32;

    private CompiledValue|null $compiledValue = null;

    private function __construct(
        private Reflector $reflector,
        private ClassConst $node,
        private int $positionInNode,
        private ReflectionClass $declaringClass,
        private ReflectionClass $implementingClass,
    ) {
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

    /**
     * Get the name of the reflection (e.g. if this is a ReflectionClass this
     * will be the class name).
     */
    public function getName(): string
    {
        return $this->node->consts[$this->positionInNode]->name->name;
    }

    /**
     * Returns constant value
     */
    public function getValue(): mixed
    {
        if ($this->compiledValue === null) {
            $this->compiledValue = (new CompileNodeToValue())->__invoke(
                $this->node->consts[$this->positionInNode]->value,
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
        return $this->node->isPublic();
    }

    /**
     * Constant is private
     */
    public function isPrivate(): bool
    {
        return $this->node->isPrivate();
    }

    /**
     * Constant is protected
     */
    public function isProtected(): bool
    {
        return $this->node->isProtected();
    }

    public function isFinal(): bool
    {
        return $this->node->isFinal();
    }

    /**
     * Returns a bitfield of the access modifiers for this constant
     */
    public function getModifiers(): int
    {
        $val  = $this->isPublic() ? CoreReflectionClassConstant::IS_PUBLIC : 0;
        $val += $this->isProtected() ? CoreReflectionClassConstant::IS_PROTECTED : 0;
        $val += $this->isPrivate() ? CoreReflectionClassConstant::IS_PRIVATE : 0;
        $val += $this->isFinal() ? self::IS_FINAL : 0;

        return $val;
    }

    /**
     * Get the line number that this constant starts on.
     */
    public function getStartLine(): int
    {
        return $this->node->getStartLine();
    }

    /**
     * Get the line number that this constant ends on.
     */
    public function getEndLine(): int
    {
        return $this->node->getEndLine();
    }

    public function getStartColumn(): int
    {
        return CalculateReflectionColumn::getStartColumn($this->declaringClass->getLocatedSource()->getSource(), $this->node);
    }

    public function getEndColumn(): int
    {
        return CalculateReflectionColumn::getEndColumn($this->declaringClass->getLocatedSource()->getSource(), $this->node);
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
    public function getDocComment(): string
    {
        return GetLastDocComment::forNode($this->node);
    }

    public function isDeprecated(): bool
    {
        return AnnotationHelper::isDeprecated($this->getDocComment());
    }

    public function __toString(): string
    {
        return ReflectionClassConstantStringCast::toString($this);
    }

    public function getAst(): ClassConst
    {
        return $this->node;
    }

    public function getPositionInAst(): int
    {
        return $this->positionInNode;
    }

    /** @return list<ReflectionAttribute> */
    public function getAttributes(): array
    {
        return ReflectionAttributeHelper::createAttributes($this->reflector, $this);
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
