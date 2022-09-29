<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection\Exception;

use Roave\BetterReflection\Reflection\ReflectionClass;
use UnexpectedValueException;

use function sprintf;

class NotATraitReflection extends UnexpectedValueException
{
    public static function fromReflectionClass(ReflectionClass $class): self
    {
        $type = 'class';

        if ($class->isInterface()) {
            $type = 'interface';
        }

        return new self(sprintf('Provided node "%s" is not trait, but "%s"', $class->getName(), $type));
    }
}
