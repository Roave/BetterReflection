<?php

namespace BetterReflection\Reflector;

use BetterReflection\Reflection\Reflection;

/**
 * This interface is used to ensure a reflector implements these basic methods
 */
interface Reflector
{
    /**
     * Create a reflection from the named identifier
     *
     * @param string $identifierName
     * @return Reflection
     */
    public function reflect($identifierName);
}
