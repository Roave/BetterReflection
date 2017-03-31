<?php

namespace Roave\BetterReflection\NodeCompiler;

use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflector\Reflector;

class CompilerContext
{
    /**
     * @var Reflector
     */
    private $reflector;

    /**
     * @var ReflectionClass|null
     */
    private $self;

    /**
     * @param Reflector $reflector
     * @param ReflectionClass|null $self
     */
    public function __construct(Reflector $reflector, ?ReflectionClass $self)
    {
        $this->reflector = $reflector;
        $this->self = $self;
    }

    /**
     * Does the current context have a "self" or "this"
     *
     * (e.g. if the context is a function, then no, there will be no self)
     *
     * @return bool
     */
    public function hasSelf() : bool
    {
        return null !== $this->self;
    }

    /**
     * Get the
     *
     * @return ReflectionClass|null
     */
    public function getSelf() : ?ReflectionClass
    {
        if (!$this->hasSelf()) {
            throw new \RuntimeException('The current context does not have a class for self');
        }

        return $this->self;
    }

    /**
     * Get the reflector
     *
     * @return Reflector
     */
    public function getReflector() : Reflector
    {
        return $this->reflector;
    }
}
