<?php
declare(strict_types = 1);

namespace Roave\BetterReflection\Util\Autoload\ClassLoaderMethod\Exception;

use Roave\BetterReflection\Reflection\ReflectionClass;

final class SignatureCheckFailed extends \RuntimeException
{
    /**
     * @param ReflectionClass $reflectionClass
     * @return self
     */
    public static function fromReflectionClass(ReflectionClass $reflectionClass)
    {
        return new self(sprintf(
            'Failed to verify the signature of the cached file for %s',
            $reflectionClass->getName()
        ));
    }
}
