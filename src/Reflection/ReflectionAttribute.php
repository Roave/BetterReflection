<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection;

use Attribute;
use PhpParser\Node;
use Roave\BetterReflection\NodeCompiler\CompileNodeToValue;
use Roave\BetterReflection\NodeCompiler\CompilerContext;
use Roave\BetterReflection\Reflection\StringCast\ReflectionAttributeStringCast;
use Roave\BetterReflection\Reflector\Reflector;

use function array_map;
use function assert;

class ReflectionAttribute
{
    /** @var non-empty-string */
    private string $name;

    /** @var array<int|string, Node\Expr> */
    private array $arguments = [];

    public function __construct(
        private Reflector $reflector,
        Node\Attribute $node,
        private ReflectionClass|ReflectionMethod|ReflectionFunction|ReflectionClassConstant|ReflectionEnumCase|ReflectionProperty|ReflectionParameter $owner,
        private bool $isRepeated,
    ) {
        $name = $node->name->toString();
        assert($name !== '');
        $this->name = $name;

        foreach ($node->args as $argNo => $arg) {
            $this->arguments[$arg->name?->toString() ?? $argNo] = $arg->value;
        }
    }

    /** @return non-empty-string */
    public function getName(): string
    {
        return $this->name;
    }

    public function getClass(): ReflectionClass
    {
        return $this->reflector->reflectClass($this->getName());
    }

    /** @return array<int|string, mixed> */
    public function getArguments(): array
    {
        $compiler = new CompileNodeToValue();
        $context  = new CompilerContext($this->reflector, $this->owner);

        return array_map(static fn (Node\Expr $value): mixed => $compiler->__invoke($value, $context)->value, $this->arguments);
    }

    public function getTarget(): int
    {
        return match (true) {
            $this->owner instanceof ReflectionClass => Attribute::TARGET_CLASS,
            $this->owner instanceof ReflectionFunction => Attribute::TARGET_FUNCTION,
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

    public function __toString(): string
    {
        return ReflectionAttributeStringCast::toString($this);
    }
}
