<?php
declare(strict_types=1);

namespace Roave\BetterReflection\SourceLocator\Located;

/**
 * {@inheritDoc}
 */
class InternalLocatedSource extends LocatedSource
{
    /**
     * {@inheritDoc}
     */
    public function __construct(string $source)
    {
        parent::__construct($source, null);
    }

    /**
     * {@inheritDoc}
     */
    public function isInternal() : bool
    {
        return true;
    }
}
