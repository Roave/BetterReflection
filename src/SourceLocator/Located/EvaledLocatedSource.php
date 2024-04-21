<?php

declare(strict_types=1);

namespace Roave\BetterReflection\SourceLocator\Located;

/**
 * @internal
 *
 * @psalm-immutable
 */
final class EvaledLocatedSource extends LocatedSource
{
    public function isEvaled(): bool
    {
        return true;
    }
}
