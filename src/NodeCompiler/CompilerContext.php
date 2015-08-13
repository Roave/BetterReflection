<?php

namespace BetterReflection\NodeCompiler;

use BetterReflection\Reflection\ReflectionClass;
use BetterReflection\Reflector\Reflector;

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
    public function __construct(Reflector $reflector, ReflectionClass $self = null)
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
    public function hasSelf()
    {
        return null !== $this->self;
    }

    /**
     * Get the
     *
     * @return ReflectionClass|null
     */
    public function getSelf()
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
    public function getReflector()
    {
        return $this->reflector;
    }
}
