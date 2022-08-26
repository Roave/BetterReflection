<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection;

use Attribute;
use PhpParser\Node;
use Roave\BetterReflection\NodeCompiler\CompileNodeToValue;
use Roave\BetterReflection\NodeCompiler\CompilerContext;
use Roave\BetterReflection\Reflection\StringCast\ReflectionAttributeStringCast;
use Roave\BetterReflection\Reflector\Reflector;

class ReflectionAttribute
{
    public function __construct(
        private Reflector $reflector,
        private Node\Attribute $node,
        private ReflectionClass|ReflectionMethod|ReflectionFunction|ReflectionClassConstant|ReflectionEnumCase|ReflectionProperty|ReflectionParameter $owner,
        private bool $isRepeated,
    ) {
    }

    public function getName(): string
    {
        return $this->node->name->toString();
    }

    public function getClass(): ReflectionClass
    {
        return $this->reflector->reflectClass($this->getName());
    }

    /** @return array<int|string, mixed> */
    public function getArguments(): array
    {
        $arguments = [];

        $compiler = new CompileNodeToValue();
        $context  = new CompilerContext($this->reflector, $this->owner);

        foreach ($this->node->args as $argNo => $arg) {
            /** @psalm-suppress MixedAssignment */
            $arguments[$arg->name?->toString() ?? $argNo] = $compiler->__invoke($arg->value, $context)->value;
        }

        return $arguments;
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
