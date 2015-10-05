<?php

namespace BetterReflection\Reflector;

use BetterReflection\Identifier\Identifier;
use BetterReflection\Identifier\IdentifierType;
use BetterReflection\Reflector\Generic as GenericReflector;
use BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use BetterReflection\SourceLocator\Type\AutoloadSourceLocator;
use BetterReflection\SourceLocator\Type\EvaledCodeSourceLocator;
use BetterReflection\SourceLocator\Type\PhpInternalSourceLocator;
use BetterReflection\SourceLocator\Type\SourceLocator;

class ClassReflector implements Reflector
{
    /**
     * @var GenericReflector
     */
    private $reflector;

    /**
     * @param SourceLocator $sourceLocator
     */
    public function __construct(SourceLocator $sourceLocator)
    {
        $this->reflector = new GenericReflector($sourceLocator);
    }

    /**
     * @return self
     */
    public static function buildDefaultReflector()
    {
        return new self(new AggregateSourceLocator([
            new PhpInternalSourceLocator(),
            new EvaledCodeSourceLocator(),
            new AutoloadSourceLocator(),
        ]));
    }

    /**
     * Create a ReflectionClass for the specified $className.
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
     * Get all the classes available in the scope specified by the SourceLocator.
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
