<?php

declare(strict_types=1);

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

    /**
     * @var ClassReflector
     */
    private $classReflector;

    public function __construct(SourceLocator $sourceLocator, ClassReflector $classReflector)
    {
        $this->sourceLocator  = $sourceLocator;
        $this->classReflector = $classReflector;
    }

    /**
     * Create a ReflectionFunction for the specified $functionName.
     *
     * @return \Roave\BetterReflection\Reflection\ReflectionFunction
     * @throws \Roave\BetterReflection\Reflector\Exception\IdentifierNotFound
     */
    public function reflect(string $functionName) : Reflection
    {
        $identifier = new Identifier($functionName, new IdentifierType(IdentifierType::IDENTIFIER_FUNCTION));

        /** @var \Roave\BetterReflection\Reflection\ReflectionFunction|null $functionInfo */
        $functionInfo = $this->sourceLocator->locateIdentifier($this->classReflector, $identifier);

        if ($functionInfo === null) {
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
        /** @var \Roave\BetterReflection\Reflection\ReflectionFunction[] $allFunctions */
        $allFunctions = $this->sourceLocator->locateIdentifiersByType(
            $this,
            new IdentifierType(IdentifierType::IDENTIFIER_FUNCTION)
        );

        return $allFunctions;
    }
}
