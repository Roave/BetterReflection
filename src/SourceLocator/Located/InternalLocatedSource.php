<?php

namespace BetterReflection\SourceLocator\Located;

/**
 * {@inheritDoc}
 */
class InternalLocatedSource extends DefiniteLocatedSource
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
    public function isInternal()
    {
        return true;
    }
}
