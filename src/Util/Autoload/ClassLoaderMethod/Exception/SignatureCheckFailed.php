<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Util\Autoload\ClassLoaderMethod\Exception;

use Roave\BetterReflection\Reflection\ReflectionClass;
use RuntimeException;

use function sprintf;

final class SignatureCheckFailed extends RuntimeException
{
    public static function fromReflectionClass(ReflectionClass $reflectionClass): self
    {
        return new self(sprintf(
            'Failed to verify the signature of the cached file for %s',
            $reflectionClass->getName(),
        ));
    }
}
