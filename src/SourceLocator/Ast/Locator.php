<?php
declare(strict_types=1);

namespace Roave\BetterReflection\SourceLocator\Ast;

use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\Identifier\IdentifierType;
use Roave\BetterReflection\Reflection\Reflection;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\Located\LocatedSource;

/**
 * @internal
 */
interface Locator
{
    /**
     * @param Reflector $reflector
     * @param LocatedSource $locatedSource
     * @param Identifier $identifier
     * @return Reflection
     * @throws \Roave\BetterReflection\Reflector\Exception\IdentifierNotFound
     * @throws Exception\ParseToAstFailure
     */
    public function findReflection(
        Reflector $reflector,
        LocatedSource $locatedSource,
        Identifier $identifier
    ) : Reflection;

    /**
     * Get an array of reflections found in some code.
     *
     * @param Reflector $reflector
     * @param LocatedSource $locatedSource
     * @param IdentifierType $identifierType
     * @return \Roave\BetterReflection\Reflection\Reflection[]
     * @throws Exception\ParseToAstFailure
     */
    public function findReflectionsOfType(
        Reflector $reflector,
        LocatedSource $locatedSource,
        IdentifierType $identifierType
    ) : array;
}
