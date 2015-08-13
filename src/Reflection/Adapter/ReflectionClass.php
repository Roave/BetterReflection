<?php

namespace BetterReflection\Reflection\Adapter;

use ReflectionClass as CoreReflectionClass;
use BetterReflection\Reflection\ReflectionClass as BetterReflectionClass;

class ReflectionClass extends CoreReflectionClass
{
    /**
     * @var BetterReflectionClass
     */
    private $betterReflectionClass;

    public function __construct(BetterReflectionClass $betterReflectionClass)
    {
        $this->betterReflectionClass = $betterReflectionClass;
    }

    public function __call($method, $args)
    {
        $this->betterReflectionClass->$method(...$args);
    }
}
