<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Util\Autoload\Exception;

use LogicException;
use Roave\BetterReflection\Reflection\ReflectionClass;
use function sprintf;

final class ClassAlreadyRegistered extends LogicException
{
    public static function fromReflectionClass(ReflectionClass $reflectionClass) : self
    {
        return new self(sprintf('Class %s already registered', $reflectionClass->getName()));
    }
}
