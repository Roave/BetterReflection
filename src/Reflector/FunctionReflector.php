<?php

namespace BetterReflection\Reflector;

use BetterReflection\Identifier\Identifier;
use BetterReflection\Identifier\IdentifierType;
use BetterReflection\Reflector\Generic as GenericReflector;
use BetterReflection\SourceLocator\SourceLocator;

class FunctionReflector implements Reflector
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
     * Create a ReflectionClass for the specified $className
     *
     * @param string $functionName
     * @return \BetterReflection\Reflection\ReflectionFunction
     */
    public function reflect($functionName)
    {
        return $this->reflector->reflect(
            new Identifier($functionName, new IdentifierType(IdentifierType::IDENTIFIER_FUNCTION))
        );
    }
}
