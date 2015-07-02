<?php

namespace BetterReflection\Reflector;

use BetterReflection\Reflection\Symbol;
use BetterReflection\Reflector\Generic as GenericReflector;
use BetterReflection\SourceLocator\SourceLocator;

class ClassReflector implements Reflector
{
    /**
     * @var GenericReflector
     */
    private $reflector;

    public function __construct(SourceLocator $sourceLocator)
    {
        $this->reflector = new GenericReflector($sourceLocator);
    }

    /**
     * @param $className
     * @return \BetterReflection\Reflection\ReflectionClass
     */
    public function reflect($className)
    {
        $symbol = new Symbol($className, Symbol::SYMBOL_CLASS);
        return $this->reflector->reflect($symbol);
    }

    /**
     * @return \BetterReflection\Reflection\ReflectionClass[]
     */
    public function getAllSymbols()
    {
        return $this->reflector->getAllSymbols(Symbol::SYMBOL_CLASS);
    }
}
