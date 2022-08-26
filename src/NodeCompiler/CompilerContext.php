<?php

declare(strict_types=1);

namespace Roave\BetterReflection\NodeCompiler;

use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionClassConstant;
use Roave\BetterReflection\Reflection\ReflectionConstant;
use Roave\BetterReflection\Reflection\ReflectionEnumCase;
use Roave\BetterReflection\Reflection\ReflectionFunction;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use Roave\BetterReflection\Reflection\ReflectionParameter;
use Roave\BetterReflection\Reflection\ReflectionProperty;
use Roave\BetterReflection\Reflector\Reflector;

/** @internal */
class CompilerContext
{
    public function __construct(
        private Reflector $reflector,
        private ReflectionClass|ReflectionProperty|ReflectionClassConstant|ReflectionEnumCase|ReflectionMethod|ReflectionFunction|ReflectionParameter|ReflectionConstant $contextReflection,
    ) {
    }

    public function getReflector(): Reflector
    {
        return $this->reflector;
    }

    public function getFileName(): string|null
    {
        if ($this->contextReflection instanceof ReflectionConstant) {
            return $this->contextReflection->getFileName();
        }

        return $this->getClass()?->getFileName() ?? $this->getFunction()?->getFileName();
    }

    public function getNamespace(): string
    {
        if ($this->contextReflection instanceof ReflectionConstant) {
            return $this->contextReflection->getNamespaceName();
        }

        return $this->getClass()?->getNamespaceName() ?? $this->getFunction()?->getNamespaceName() ?? '';
    }

    public function getClass(): ReflectionClass|null
    {
        if ($this->contextReflection instanceof ReflectionClass) {
            return $this->contextReflection;
        }

        if ($this->contextReflection instanceof ReflectionFunction) {
            return null;
        }

        if ($this->contextReflection instanceof ReflectionConstant) {
            return null;
        }

        if ($this->contextReflection instanceof ReflectionClassConstant) {
            return $this->contextReflection->getDeclaringClass();
        }

        if ($this->contextReflection instanceof ReflectionEnumCase) {
            return $this->contextReflection->getDeclaringClass();
        }

        return $this->contextReflection->getImplementingClass();
    }

    public function getFunction(): ReflectionMethod|ReflectionFunction|null
    {
        if ($this->contextReflection instanceof ReflectionMethod) {
            return $this->contextReflection;
        }

        if ($this->contextReflection instanceof ReflectionFunction) {
            return $this->contextReflection;
        }

        if ($this->contextReflection instanceof ReflectionParameter) {
            return $this->contextReflection->getDeclaringFunction();
        }

        return null;
    }
}
