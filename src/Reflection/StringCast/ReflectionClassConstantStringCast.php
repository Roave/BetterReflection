<?php
declare(strict_types=1);

namespace Rector\BetterReflection\Reflection\StringCast;

use Rector\BetterReflection\Reflection\ReflectionClassConstant;

/**
 * @internal
 */
final class ReflectionClassConstantStringCast
{
    public static function toString(ReflectionClassConstant $constantReflection) : string
    {
        $value = $constantReflection->getValue();

        return \sprintf(
            "Constant [ %s %s %s ] { %s }\n",
            self::visibilityToString($constantReflection),
            \gettype($value),
            $constantReflection->getName(),
            $value
        );
    }

    private static function visibilityToString(ReflectionClassConstant $constantReflection) : string
    {
        if ($constantReflection->isProtected()) {
            return 'protected';
        }

        if ($constantReflection->isPrivate()) {
            return 'private';
        }

        return 'public';
    }
}
