<?php

namespace BetterReflection\Reflector;

use BetterReflection\Identifier\Identifier;
use BetterReflection\Identifier\IdentifierType;
use BetterReflection\Reflection\Reflection;
use BetterReflection\Reflection\ReflectionClass;
use BetterReflection\Reflection\ReflectionFunction;
use BetterReflection\SourceLocator\Type\SourceLocator;

class CombinedReflector implements Reflector
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
     * @param string $itemName
     * @return ReflectionClass|ReflectionFunction
     */
    public function reflect($itemName)
    {
        $reflectionClass = $this->sourceLocator->locateIdentifier(
            $this,
            new Identifier($itemName, new IdentifierType(IdentifierType::IDENTIFIER_CLASS))
        );
        
        if ($reflectionClass instanceof ReflectionClass) {
            return $reflectionClass;
        }

        $reflectionFunction = $this->sourceLocator->locateIdentifier(
            $this,
            new Identifier($itemName, new IdentifierType(IdentifierType::IDENTIFIER_FUNCTION))
        );

        if ($reflectionFunction instanceof ReflectionFunction) {
            return $reflectionFunction;
        }

        return null;
    }

    /**
     * @return Reflection[]
     */
    public function getAll()
    {
        return array_merge(
            $this->sourceLocator->locateIdentifiersByType(
                $this,
                new IdentifierType(IdentifierType::IDENTIFIER_CLASS)
            ),
            $this->sourceLocator->locateIdentifiersByType(
                $this,
                new IdentifierType(IdentifierType::IDENTIFIER_FUNCTION)
            )
        );
    }

    /**
     * Find a reflection on the specified line number.
     *
     * Returns null if no reflections found on the line.
     *
     * @param int $lineNumber
     * @return ReflectionFunction|ReflectionClass|null
     */
    public function reflectOnLine($lineNumber)
    {
        $reflections = $this->getAll();
        foreach ($reflections as $reflection) {
            if (method_exists($reflection, 'getStartLine')
                && $reflection->getStartLine() === $lineNumber) {
                return $reflection;
            }
        }
        return null;
    }
}
