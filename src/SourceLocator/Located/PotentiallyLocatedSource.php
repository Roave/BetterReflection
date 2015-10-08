<?php

namespace BetterReflection\SourceLocator\Located;

/**
 * {@inheritDoc}
 */
class PotentiallyLocatedSource extends LocatedSource
{
    /**
     * {@inheritDoc}
     */
    public function __construct($source, $filename)
    {
        parent::__construct($source, $filename);
    }
}
