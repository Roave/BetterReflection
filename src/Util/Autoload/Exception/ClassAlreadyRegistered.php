<?php
declare(strict_types=1);

namespace Roave\BetterReflection\Util\Autoload\Exception;

use Roave\BetterReflection\Reflection\ReflectionClass;

final class ClassAlreadyRegistered extends \LogicException
{
    public static function fromReflectionClass(ReflectionClass $reflectionClass) : self
    {
        return new self(sprintf('Class %s already registered', $reflectionClass->getName()));
    }
}
