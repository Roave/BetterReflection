<?php

declare(strict_types=1);

namespace Roave\BetterReflection\NodeCompiler;

use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflector\Reflector;
use RuntimeException;

class CompilerContext
{
    /** @var Reflector */
    private $reflector;

    /** @var ReflectionClass|null */
    private $self;

    public function __construct(Reflector $reflector, ?ReflectionClass $self)
    {
        $this->reflector = $reflector;
        $this->self      = $self;
    }

    /**
     * Does the current context have a "self" or "this"
     *
     * (e.g. if the context is a function, then no, there will be no self)
     */
    public function hasSelf() : bool
    {
        return $this->self !== null;
    }

    public function getSelf() : ReflectionClass
    {
        if (! $this->hasSelf()) {
            throw new RuntimeException('The current context does not have a class for self');
        }

        return $this->self;
    }

    public function getReflector() : Reflector
    {
        return $this->reflector;
    }

    public function getFileName() : string
    {
        return $this->getSelf()->getFileName();
    }
}
