<?php

declare(strict_types=1);

namespace Roave\BetterReflection\SourceLocator\Located;

/** @internal */
class AnonymousLocatedSource extends LocatedSource
{
    public function __construct(string $source, string $filename)
    {
        parent::__construct($source, null, $filename);
    }
}
