<?php
declare(strict_types=1);

namespace Rector\BetterReflection\SourceLocator\Type;

use Rector\BetterReflection\Identifier\Identifier;
use Rector\BetterReflection\Identifier\IdentifierType;
use Rector\BetterReflection\Reflection\Reflection;
use Rector\BetterReflection\Reflector\Reflector;

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
    public function locateIdentifier(Reflector $reflector, Identifier $identifier) : ?Reflection;

    /**
     * Find all identifiers of a type
     *
     * @param Reflector $reflector
     * @param IdentifierType $identifierType
     * @return \Rector\BetterReflection\Reflection\Reflection[]
     */
    public function locateIdentifiersByType(Reflector $reflector, IdentifierType $identifierType) : array;
}
