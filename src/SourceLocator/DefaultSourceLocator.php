<?php

namespace BetterReflection\SourceLocator;

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
