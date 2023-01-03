<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection;

use LogicException;
use PhpParser\Node;
use PhpParser\Node\Stmt\EnumCase;
use Roave\BetterReflection\NodeCompiler\CompiledValue;
use Roave\BetterReflection\NodeCompiler\CompileNodeToValue;
use Roave\BetterReflection\NodeCompiler\CompilerContext;
use Roave\BetterReflection\Reflection\Annotation\AnnotationHelper;
use Roave\BetterReflection\Reflection\Attribute\ReflectionAttributeHelper;
use Roave\BetterReflection\Reflection\StringCast\ReflectionEnumCaseStringCast;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\Util\CalculateReflectionColumn;
use Roave\BetterReflection\Util\GetLastDocComment;

use function assert;
use function is_int;
use function is_string;

/** @psalm-immutable */
class ReflectionEnumCase
{
    /** @var non-empty-string */
    private string $name;

    private Node\Expr|null $value;

    /** @var list<ReflectionAttribute> */
    private array $attributes;

    /** @var non-empty-string|null */
    private string|null $docComment;

    /** @var positive-int */
    private int $startLine;

    /** @var positive-int */
    private int $endLine;

    /** @var positive-int */
    private int $startColumn;

    /** @var positive-int */
    private int $endColumn;

    /** @psalm-allow-private-mutation */
    private CompiledValue|null $compiledValue = null;

    private function __construct(
        private Reflector $reflector,
        EnumCase $node,
        private ReflectionEnum $enum,
    ) {
        $name = $node->name->toString();
        assert($name !== '');
        $this->name = $name;

        $this->value      = $node->expr;
        $this->attributes = ReflectionAttributeHelper::createAttributes($reflector, $this, $node->attrGroups);
        $this->docComment = GetLastDocComment::forNode($node);

        $startLine = $node->getStartLine();
        assert($startLine > 0);
        $endLine = $node->getEndLine();
        assert($endLine > 0);

        $this->startLine   = $startLine;
        $this->endLine     = $endLine;
        $this->startColumn = CalculateReflectionColumn::getStartColumn($this->enum->getLocatedSource()->getSource(), $node);
        $this->endColumn   = CalculateReflectionColumn::getEndColumn($this->enum->getLocatedSource()->getSource(), $node);
    }

    /** @internal */
    public static function createFromNode(
        Reflector $reflector,
        EnumCase $node,
        ReflectionEnum $enum,
    ): self {
        return new self($reflector, $node, $enum);
    }

    /** @return non-empty-string */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Check ReflectionEnum::isBacked() being true first to avoid throwing exception.
     *
     * @throws LogicException
     */
    public function getValueExpression(): Node\Expr
    {
        if ($this->value === null) {
            throw new LogicException('This enum case does not have a value');
        }

        return $this->value;
    }

    public function getValue(): string|int
    {
        $value = $this->getCompiledValue()->value;
        assert(is_string($value) || is_int($value));

        return $value;
    }

    /**
     * Check ReflectionEnum::isBacked() being true first to avoid throwing exception.
     *
     * @throws LogicException
     */
    private function getCompiledValue(): CompiledValue
    {
        if ($this->value === null) {
            throw new LogicException('This enum case does not have a value');
        }

        if ($this->compiledValue === null) {
            $this->compiledValue = (new CompileNodeToValue())->__invoke(
                $this->value,
                new CompilerContext($this->reflector, $this),
            );
        }

        return $this->compiledValue;
    }

    /** @return positive-int */
    public function getStartLine(): int
    {
        return $this->startLine;
    }

    /** @return positive-int */
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

    public function getDeclaringEnum(): ReflectionEnum
    {
        return $this->enum;
    }

    public function getDeclaringClass(): ReflectionClass
    {
        return $this->enum;
    }

    /** @return non-empty-string|null */
    public function getDocComment(): string|null
    {
        return $this->docComment;
    }

    public function isDeprecated(): bool
    {
        return AnnotationHelper::isDeprecated($this->docComment);
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

    /** @return non-empty-string */
    public function __toString(): string
    {
        return ReflectionEnumCaseStringCast::toString($this);
    }
}
