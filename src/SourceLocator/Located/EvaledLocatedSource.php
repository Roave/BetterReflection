<?php

declare(strict_types=1);

namespace Roave\BetterReflection\SourceLocator\Located;

class EvaledLocatedSource extends LocatedSource
{
    public function isEvaled(): bool
    {
        return true;
    }
}
