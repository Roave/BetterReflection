<?php
declare(strict_types=1);

namespace Rector\BetterReflection\Reflector;

use Rector\BetterReflection\Identifier\Identifier;
use Rector\BetterReflection\Identifier\IdentifierType;
use Rector\BetterReflection\Reflection\Reflection;
use Rector\BetterReflection\SourceLocator\Type\SourceLocator;

class ClassReflector implements Reflector
{
    /**
     * @var SourceLocator
     */
    private $sourceLocator;

    /**
     * @param SourceLocator $sourceLocator
     */
    public function __construct(SourceLocator $sourceLocator)
    {
        $this->sourceLocator = $sourceLocator;
    }

    /**
     * Create a ReflectionClass for the specified $className.
     *
     * @param string $className
     * @return \Rector\BetterReflection\Reflection\ReflectionClass|Reflection
     * @throws \Rector\BetterReflection\Reflector\Exception\IdentifierNotFound
     */
    public function reflect(string $className) : Reflection
    {
        $identifier = new Identifier($className, new IdentifierType(IdentifierType::IDENTIFIER_CLASS));

        $classInfo = $this->sourceLocator->locateIdentifier($this, $identifier);

        if (null === $classInfo) {
            throw Exception\IdentifierNotFound::fromIdentifier($identifier);
        }

        return $classInfo;
    }

    /**
     * Get all the classes available in the scope specified by the SourceLocator.
     *
     * @return \Rector\BetterReflection\Reflection\ReflectionClass[]
     */
    public function getAllClasses() : array
    {
        /** @var \Rector\BetterReflection\Reflection\ReflectionClass[] $allClasses */
        $allClasses = $this->sourceLocator->locateIdentifiersByType(
            $this,
            new IdentifierType(IdentifierType::IDENTIFIER_CLASS)
        );

        return $allClasses;
    }
}
