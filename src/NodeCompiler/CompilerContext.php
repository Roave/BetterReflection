<?php

declare(strict_types=1);

namespace Roave\BetterReflection\NodeCompiler;

use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionFunctionAbstract;
use Roave\BetterReflection\Reflector\Reflector;

/**
 * @internal
 */
class CompilerContext
{
    public function __construct(private Reflector $reflector, private ?string $fileName, private ?string $namespace, private ?ReflectionClass $class, private ?ReflectionFunctionAbstract $function)
    {
    }

    public function getReflector(): Reflector
    {
        return $this->reflector;
    }

    public function getFileName(): ?string
    {
        return $this->fileName;
    }

    public function getNamespace(): ?string
    {
        return $this->namespace;
    }

    public function getClass(): ?ReflectionClass
    {
        return $this->class;
    }

    public function getFunction(): ?ReflectionFunctionAbstract
    {
        return $this->function;
    }
}
