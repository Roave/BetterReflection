<?php

declare(strict_types=1);

namespace Roave\BetterReflection\SourceLocator\Exception;

use RuntimeException;

use function gettype;
use function is_object;
use function sprintf;

class InvalidFileInfo extends RuntimeException
{
    public static function fromNonSplFileInfo(mixed $nonSplFileInfo): self
    {
        return new self(sprintf(
            'Expected an iterator of SplFileInfo instances, %s given instead',
            is_object($nonSplFileInfo) ? $nonSplFileInfo::class : gettype($nonSplFileInfo),
        ));
    }
}
