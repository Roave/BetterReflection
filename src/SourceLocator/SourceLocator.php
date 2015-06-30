<?php

namespace BetterReflection\SourceLocator;

interface SourceLocator
{
    /**
     * Locate some source code
     *
     * This method should return a LocatedSource value object
     *
     * @param string $className
     * @return LocatedSource
     */
    public function locate($className);
}
