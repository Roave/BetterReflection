<?php

namespace BetterReflection\Reflector;

use BetterReflection\Identifier\Identifier;
use BetterReflection\Identifier\IdentifierType;
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
     * Create a ReflectionClass for the specified $className
     *
     * @param string $className
     * @return \BetterReflection\Reflection\ReflectionClass
     */
    public function reflect($className)
    {
        return $this->reflector->reflect(
            new Identifier($className, new IdentifierType(IdentifierType::IDENTIFIER_CLASS))
        );
    }

    /**
     * Get all the classes available in the scope specified by the SourceLocator
     *
     * @return \BetterReflection\Reflection\ReflectionClass[]
     */
    public function getAllClasses()
    {
        return $this->reflector->getAllByIdentifierType(
            new IdentifierType(IdentifierType::IDENTIFIER_CLASS)
        );
    }
}
