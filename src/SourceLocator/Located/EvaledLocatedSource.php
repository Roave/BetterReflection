<?php

namespace BetterReflection\SourceLocator\Located;

/**
 * {@inheritDoc}
 */
class EvaledLocatedSource extends DefiniteLocatedSource
{
    /**
     * {@inheritDoc}
     */
    public function __construct($source)
    {
        parent::__construct($source, null);
    }

    /**
     * {@inheritDoc}
     */
    public function isEvaled()
    {
        return true;
    }
}
