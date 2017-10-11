<?php
declare(strict_types=1);

namespace Rector\BetterReflection\Reflection\StringCast;

use Rector\BetterReflection\Reflection\ReflectionProperty;

/**
 * @internal
 */
final class ReflectionPropertyStringCast
{
    public static function toString(ReflectionProperty $propertyReflection) : string
    {
        $stateModifier = '';

        if ( ! $propertyReflection->isStatic()) {
            $stateModifier = $propertyReflection->isDefault() ? ' <default>' : ' <dynamic>';
        }

        return \sprintf(
            'Property [%s %s%s $%s ]',
            $stateModifier,
            self::visibilityToString($propertyReflection),
            $propertyReflection->isStatic() ? ' static' : '',
            $propertyReflection->getName()
        );
    }

    private static function visibilityToString(ReflectionProperty $propertyReflection) : string
    {
        if ($propertyReflection->isProtected()) {
            return 'protected';
        }

        if ($propertyReflection->isPrivate()) {
            return 'private';
        }

        return 'public';
    }
}
