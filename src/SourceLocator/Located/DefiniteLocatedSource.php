<?php

namespace BetterReflection\SourceLocator\Located;

/**
 * {@inheritDoc}
 */
class DefiniteLocatedSource extends LocatedSource
{
    /**
     * Create a definite located source from a potentially located source
     *
     * (semantic meaning is distinct)
     *
     * @param PotentiallyLocatedSource $potentiallyLocatedSource
     * @return DefiniteLocatedSource
     */
    public static function fromPotentiallyLocatedSource(PotentiallyLocatedSource $potentiallyLocatedSource)
    {
        return new self($potentiallyLocatedSource->getSource(), $potentiallyLocatedSource->getFileName());
    }
}
