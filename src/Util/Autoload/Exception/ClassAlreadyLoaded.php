<?php
declare(strict_types=1);

namespace Rector\BetterReflection\Util\Autoload\Exception;

use LogicException;
use Rector\BetterReflection\Reflection\ReflectionClass;

final class ClassAlreadyLoaded extends LogicException
{
    public static function fromReflectionClass(ReflectionClass $reflectionClass) : self
    {
        return new self(\sprintf(
            'Class %s has already been loaded into memory so cannot be modified',
            $reflectionClass->getName()
        ));
    }
}
