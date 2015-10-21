<?php

namespace BetterReflection\SourceLocator\Type;

use BetterReflection\Identifier\Identifier;
use BetterReflection\Identifier\IdentifierType;
use BetterReflection\Reflection\Reflection;
use BetterReflection\Reflector\Reflector;

interface SourceLocator
{
    /**
     * Locate some source code.
     *
     * This method should return a LocatedSource value object or `null` if the
     * SourceLocator is unable to locate the source.
     *
     * NOTE: A SourceLocator should *NOT* throw an exception if it is unable to
     * locate the identifier, it should simply return null. If an exception is
     * thrown, it will break the Generic Reflector.
     *
     * @param Reflector $reflector
     * @param Identifier $identifier
     * @return Reflection|null
     */
    public function locateIdentifier(Reflector $reflector, Identifier $identifier);

    /**
     * Find all identifiers of a type
     *
     * @param Reflector $reflector
     * @param IdentifierType $identifierType
     * @return \BetterReflection\Reflection\Reflection[]
     */
    public function locateIdentifiersByType(Reflector $reflector, IdentifierType $identifierType);
}
