<?php

namespace BetterReflection\SourceLocator;

/**
 * {@inheritDoc}
 */
class EvaledLocatedSource extends LocatedSource
{
    /**
     * @param string $fileName
     */
    public function __construct($fileName)
    {
        parent::__construct($fileName, null);
    }

    /**
     * {@inheritDoc}
     */
    public function isEvaled()
    {
        return true;
    }
}
