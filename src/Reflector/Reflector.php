<?php

namespace Roave\BetterReflection\Reflector;

use Roave\BetterReflection\Reflection\Reflection;
use Roave\BetterReflection\Reflector\Exception\IdentifierNotFound;

/**
 * This interface is used to ensure a reflector implements these basic methods.
 */
interface Reflector
{
    /**
     * Create a reflection from the named identifier.
     *
     * @param string $identifierName
     * @return Reflection
     * @throws IdentifierNotFound
     */
    public function reflect(string $identifierName) : Reflection;
}
