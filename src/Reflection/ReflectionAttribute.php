<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection;

use Attribute;
use PhpParser\Node;
use Roave\BetterReflection\NodeCompiler\CompileNodeToValue;
use Roave\BetterReflection\NodeCompiler\CompilerContext;
use Roave\BetterReflection\Reflection\Adapter\ReflectionAttribute as ReflectionAttributeAdapter;
use Roave\BetterReflection\Reflection\StringCast\ReflectionAttributeStringCast;
use Roave\BetterReflection\Reflector\Reflector;

use function array_map;
use function assert;
use function ltrim;
use function strrpos;
use function substr;

/** @psalm-immutable */
class ReflectionAttribute
{
    /** @var class-string */
    private string $name;

    /** @var array<int|string, Node\Expr> */
    private array $arguments;

    /** @internal */
    public function __construct(
        private Reflector $reflector,
        Node\Attribute $node,
        private ReflectionClass|ReflectionMethod|ReflectionFunction|ReflectionConstant|ReflectionClassConstant|ReflectionEnumCase|ReflectionProperty|ReflectionParameter $owner,
        private bool $isRepeated,
    ) {
        /** @var class-string $name */
        $name = $node->name->toString();

        $this->name = $name;

        $arguments = [];
        foreach ($node->args as $argNo => $arg) {
            $arguments[$arg->name?->toString() ?? $argNo] = $arg->value;
        }

        $this->arguments = $arguments;
    }

    /** @internal */
    public function withOwner(ReflectionClass|ReflectionMethod|ReflectionFunction|ReflectionConstant|ReflectionClassConstant|ReflectionEnumCase|ReflectionProperty|ReflectionParameter $owner): self
    {
        $clone        = clone $this;
        $clone->owner = $owner;

        return $clone;
    }

    /** @return class-string */
    public function getName(): string
    {
        return $this->name;
    }

    /** @return non-empty-string */
    public function getShortName(): string
    {
        $name      = ltrim($this->name, '\\');
        $position  = strrpos($name, '\\');
        $shortName = $position === false ? $name : substr($name, $position + 1);

        assert($shortName !== '');

        return $shortName;
    }

    public function getNamespaceName(): string|null
    {
        $name     = ltrim($this->name, '\\');
        $position = strrpos($name, '\\');

        return $position === false ? null : substr($name, 0, $position);
    }

    public function inNamespace(): bool
    {
        return $this->getNamespaceName() !== null;
    }

    public function getClass(): ReflectionClass
    {
        return $this->reflector->reflectClass($this->getName());
    }

    /** @return array<int|string, Node\Expr> */
    public function getArgumentsExpressions(): array
    {
        return $this->arguments;
    }

    /** @return array<int|string, mixed> */
    public function getArguments(): array
    {
        $compiler = new CompileNodeToValue();
        $context  = new CompilerContext($this->reflector, $this->owner);

        return array_map(static fn (Node\Expr $value): mixed => $compiler->__invoke($value, $context)->value, $this->arguments);
    }

    /** @return int-mask-of<Attribute::TARGET_*>|ReflectionAttributeAdapter::TARGET_CONSTANT_COMPATIBILITY */
    public function getTarget(): int
    {
        return match (true) {
            $this->owner instanceof ReflectionClass => Attribute::TARGET_CLASS,
            $this->owner instanceof ReflectionFunction => Attribute::TARGET_FUNCTION,
            $this->owner instanceof ReflectionConstant => ReflectionAttributeAdapter::TARGET_CONSTANT_COMPATIBILITY,
            $this->owner instanceof ReflectionMethod => Attribute::TARGET_METHOD,
            $this->owner instanceof ReflectionProperty => Attribute::TARGET_PROPERTY,
            $this->owner instanceof ReflectionClassConstant => Attribute::TARGET_CLASS_CONSTANT,
            $this->owner instanceof ReflectionEnumCase => Attribute::TARGET_CLASS_CONSTANT,
            // @infection-ignore-all InstanceOf_: There's no other option
            $this->owner instanceof ReflectionParameter => Attribute::TARGET_PARAMETER,
        };
    }

    public function isRepeated(): bool
    {
        return $this->isRepeated;
    }

    /** @return non-empty-string */
    public function __toString(): string
    {
        return ReflectionAttributeStringCast::toString($this);
    }
}
