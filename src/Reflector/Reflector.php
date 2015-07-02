<?php

namespace BetterReflection\Reflector;

use BetterReflection\Reflection\Reflection;

/**
 * This interface is used to ensure a reflector implements these basic methods
 */
interface Reflector
{
    /**
     * Create a reflection from the named symbol
     *
     * @param string $symbolName
     * @return Reflection
     */
    public function reflect($symbolName);

    /**
     * Get all symbols specified in the available scope (usually depends on
     * the SourceLocator used)
     *
     * @return Reflection[]
     */
    public function getAllSymbols();
}
