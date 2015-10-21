<?php

namespace BetterReflection\Reflector;

use BetterReflection\Identifier\Identifier;
use BetterReflection\Identifier\IdentifierType;
use BetterReflection\SourceLocator\Type\SourceLocator;

class FunctionReflector implements Reflector
{
    /**
     * @var SourceLocator
     */
    private $sourceLocator;

    public function __construct(SourceLocator $sourceLocator)
    {
        $this->sourceLocator = $sourceLocator;
    }

    /**
     * Create a ReflectionClass for the specified $className.
     *
     * @param string $functionName
     * @return \BetterReflection\Reflection\ReflectionFunction
     */
    public function reflect($functionName)
    {
        return $this->sourceLocator->locateIdentifier(
            $this,
            new Identifier($functionName, new IdentifierType(IdentifierType::IDENTIFIER_FUNCTION))
        );
    }
}
