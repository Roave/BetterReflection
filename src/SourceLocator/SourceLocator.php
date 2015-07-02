<?php

namespace BetterReflection\SourceLocator;

use BetterReflection\Reflection\Symbol;

interface SourceLocator
{
    /**
     * Locate some source code
     *
     * This method should return a LocatedSource value object
     *
     * @param Symbol $symbol
     * @return LocatedSource
     */
    public function __invoke(Symbol $symbol);
}
