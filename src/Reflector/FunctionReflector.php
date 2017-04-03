<?php

namespace Roave\BetterReflection\Reflector;

use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\Identifier\IdentifierType;
use Roave\BetterReflection\Reflection\Reflection;
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
     * @return \Roave\BetterReflection\Reflection\Reflection|\Roave\BetterReflection\Reflection\ReflectionFunction
     * @throws \Roave\BetterReflection\Reflector\Exception\IdentifierNotFound
     */
    public function reflect(string $functionName) : Reflection
    {
        $identifier = new Identifier($functionName, new IdentifierType(IdentifierType::IDENTIFIER_FUNCTION));

        $functionInfo = $this->sourceLocator->locateIdentifier(ClassReflector::buildDefaultReflector(), $identifier);

        if (null === $functionInfo) {
            throw Exception\IdentifierNotFound::fromIdentifier($identifier);
        }

        return $functionInfo;
    }

    /**
     * Get all the functions available in the scope specified by the SourceLocator.
     *
     * @return \Roave\BetterReflection\Reflection\ReflectionFunction[]
     */
    public function getAllFunctions() : array
    {
        return $this->sourceLocator->locateIdentifiersByType(
            $this,
            new IdentifierType(IdentifierType::IDENTIFIER_FUNCTION)
        );
    }
}
