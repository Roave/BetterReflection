<?php

namespace BetterReflection\SourceLocator;

/**
 * A simplification to avoid having to define the same three source locators
 * all over the place. This should be able to locate most things in common
 * environments.
 */
class DefaultSourceLocator extends AggregateSourceLocator
{
    public function __construct()
    {
        parent::__construct([
            new PhpInternalSourceLocator(),
            new EvaledCodeSourceLocator(),
            new AutoloadSourceLocator(),
        ]);
    }
}
