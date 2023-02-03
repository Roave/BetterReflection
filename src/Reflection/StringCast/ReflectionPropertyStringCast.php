<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection\StringCast;

use Roave\BetterReflection\Reflection\ReflectionProperty;

use function assert;
use function sprintf;

/** @internal */
final class ReflectionPropertyStringCast
{
    /**
     * @return non-empty-string
     *
     * @psalm-pure
     */
    public static function toString(ReflectionProperty $propertyReflection): string
    {
        $stateModifier = '';

        if (! $propertyReflection->isStatic()) {
            $stateModifier = $propertyReflection->isDefault() ? ' <default>' : ' <dynamic>';
        }

        $type = $propertyReflection->getType();

        $string = sprintf(
            'Property [%s %s%s%s%s $%s ]',
            $stateModifier,
            self::visibilityToString($propertyReflection),
            $propertyReflection->isStatic() ? ' static' : '',
            $propertyReflection->isReadOnly() ? ' readonly' : '',
            $type !== null ? sprintf(' %s', ReflectionTypeStringCast::toString($type)) : '',
            $propertyReflection->getName(),
        );
        assert($string !== '');

        return $string;
    }

    /** @psalm-pure */
    private static function visibilityToString(ReflectionProperty $propertyReflection): string
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
