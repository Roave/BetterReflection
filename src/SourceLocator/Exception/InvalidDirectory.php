<?php

declare(strict_types=1);

namespace Roave\BetterReflection\SourceLocator\Exception;

use RuntimeException;

use function gettype;
use function is_file;
use function is_object;
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

    /**
     * @param resource|float|int|bool|object|mixed[]|null $nonStringValue
     */
    public static function fromNonStringValue($nonStringValue): self
    {
        return new self(sprintf(
            'Expected string, %s given',
            is_object($nonStringValue) ? $nonStringValue::class : gettype($nonStringValue),
        ));
    }
}
