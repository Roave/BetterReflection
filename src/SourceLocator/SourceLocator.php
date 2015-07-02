<?php

namespace BetterReflection\SourceLocator;

use BetterReflection\Identifier\Identifier;

interface SourceLocator
{
    /**
     * Locate some source code
     *
     * This method should return a LocatedSource value object
     *
     * @param Identifier $identifier
     * @return LocatedSource
     */
    public function __invoke(Identifier $identifier);
}
