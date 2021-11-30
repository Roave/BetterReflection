<?php

declare(strict_types=1);

namespace Roave\BetterReflection\SourceLocator\Exception;

use RuntimeException;

use function is_file;
use function sprintf;

class InvalidDirectory extends RuntimeException
{
    public static function fromNonDirectory(string $nonDirectory): self
    {
        if (is_file($nonDirectory)) {
            return new self(sprintf('"%s" must be a directory, not a file', $nonDirectory));
        }

        return new self(sprintf('"%s" does not exist', $nonDirectory));
    }
}
