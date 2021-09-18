<?php

declare(strict_types=1);

namespace Roave\BetterReflection\NodeCompiler;

use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionFunctionAbstract;
use Roave\BetterReflection\Reflector\Reflector;
use RuntimeException;

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

    public function inClass(): bool
    {
        return $this->class !== null;
    }

    public function getClass(): ReflectionClass
    {
        if (! $this->inClass()) {
            throw new RuntimeException('The current context does not have a class');
        }

        return $this->class;
    }

    public function inFunction(): bool
    {
        return $this->function !== null;
    }

    public function getFunction(): ReflectionFunctionAbstract
    {
        if (! $this->inFunction()) {
            throw new RuntimeException('The current context does not have a function');
        }

        return $this->function;
    }
}
