<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection\StringCast;

use Roave\BetterReflection\Reflection\ReflectionParameter;

use function is_array;
use function is_string;
use function sprintf;
use function strlen;
use function substr;
use function var_export;

/** @internal */
final class ReflectionParameterStringCast
{
    public static function toString(ReflectionParameter $parameterReflection): string
    {
        return sprintf(
            'Parameter #%d [ %s %s%s%s$%s%s ]',
            $parameterReflection->getPosition(),
            $parameterReflection->isOptional() ? '<optional>' : '<required>',
            self::typeToString($parameterReflection),
            $parameterReflection->isVariadic() ? '...' : '',
            $parameterReflection->isPassedByReference() ? '&' : '',
            $parameterReflection->getName(),
            self::valueToString($parameterReflection),
        );
    }

    private static function typeToString(ReflectionParameter $parameterReflection): string
    {
        $type = $parameterReflection->getType();

        if ($type === null) {
            return '';
        }

        return ReflectionTypeStringCast::toString($type) . ' ';
    }

    private static function valueToString(ReflectionParameter $parameterReflection): string
    {
        if (! ($parameterReflection->isOptional() && $parameterReflection->isDefaultValueAvailable())) {
            return '';
        }

        $defaultValue = $parameterReflection->getDefaultValue();

        if (is_array($defaultValue)) {
            return ' = Array';
        }

        if (is_string($defaultValue) && strlen($defaultValue) > 15) {
            return ' = ' . var_export(substr($defaultValue, 0, 15) . '...', true);
        }

        return ' = ' . var_export($defaultValue, true);
    }
}
