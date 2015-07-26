<?php

namespace BetterReflection\SourceLocator;

use BetterReflection\Identifier\Identifier;

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
     * @param Identifier $identifier
     * @return LocatedSource|null
     */
    public function __invoke(Identifier $identifier);
}
