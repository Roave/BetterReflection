<?php
declare(strict_types=1);

namespace Rector\BetterReflection\Reflector;

use Rector\BetterReflection\Identifier\Identifier;
use Rector\BetterReflection\Identifier\IdentifierType;
use Rector\BetterReflection\Reflection\Reflection;
use Rector\BetterReflection\SourceLocator\Type\SourceLocator;

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
     * @param string $functionName
     * @return \Rector\BetterReflection\Reflection\Reflection|\Rector\BetterReflection\Reflection\ReflectionFunction
     * @throws \Rector\BetterReflection\Reflector\Exception\IdentifierNotFound
     */
    public function reflect(string $functionName) : Reflection
    {
        $identifier = new Identifier($functionName, new IdentifierType(IdentifierType::IDENTIFIER_FUNCTION));

        $functionInfo = $this->sourceLocator->locateIdentifier($this->classReflector, $identifier);

        if (null === $functionInfo) {
            throw Exception\IdentifierNotFound::fromIdentifier($identifier);
        }

        return $functionInfo;
    }

    /**
     * Get all the functions available in the scope specified by the SourceLocator.
     *
     * @return \Rector\BetterReflection\Reflection\ReflectionFunction[]
     */
    public function getAllFunctions() : array
    {
        /** @var \Rector\BetterReflection\Reflection\ReflectionFunction[] $allFunctions */
        $allFunctions = $this->sourceLocator->locateIdentifiersByType(
            $this,
            new IdentifierType(IdentifierType::IDENTIFIER_FUNCTION)
        );

        return $allFunctions;
    }
}
