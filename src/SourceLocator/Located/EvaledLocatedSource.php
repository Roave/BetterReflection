<?php

declare(strict_types=1);

namespace Roave\BetterReflection\SourceLocator\Located;

class EvaledLocatedSource extends LocatedSource
{
    /**
     * {@inheritDoc}
     */
    public function __construct(string $source)
    {
        parent::__construct($source, null);
    }

    public function isEvaled() : bool
    {
        return true;
    }
}
