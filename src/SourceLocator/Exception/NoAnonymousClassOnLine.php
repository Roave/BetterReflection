<?php

declare(strict_types=1);

namespace Roave\BetterReflection\SourceLocator\Exception;

use LogicException;

use function sprintf;

class NoAnonymousClassOnLine extends LogicException
{
    public static function create(string $fileName, int $lineNumber): self
    {
        return new self(sprintf('No anonymous class found on line %d in %s', $lineNumber, $fileName));
    }
}
