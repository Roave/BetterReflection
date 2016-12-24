<?php

namespace Roave\BetterReflection\Reflector;

use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\Identifier\IdentifierType;
use Roave\BetterReflection\SourceLocator\Type\SourceLocator;

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
     * Create a ReflectionFunction for the specified $functionName.
     *
     * @param string $functionName
     * @return \Roave\BetterReflection\Reflection\ReflectionFunction
     */
    public function reflect($functionName)
    {
        return $this->sourceLocator->locateIdentifier(
            ClassReflector::buildDefaultReflector(),
            new Identifier($functionName, new IdentifierType(IdentifierType::IDENTIFIER_FUNCTION))
        );
    }
}
