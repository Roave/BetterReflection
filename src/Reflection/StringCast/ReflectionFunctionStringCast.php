<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection\StringCast;

use Roave\BetterReflection\Reflection\ReflectionFunction;
use Roave\BetterReflection\Reflection\ReflectionParameter;

use function array_reduce;
use function assert;
use function count;
use function is_string;
use function sprintf;

/** @internal */
final class ReflectionFunctionStringCast
{
    public static function toString(ReflectionFunction $functionReflection): string
    {
        $parametersFormat = $functionReflection->getNumberOfParameters() > 0 || $functionReflection->hasReturnType()
            ? "\n\n  - Parameters [%d] {%s\n  }"
            : '';

        $returnTypeFormat = $functionReflection->hasReturnType()
            ? "\n  - Return [ %s ]"
            : '';

        return sprintf(
            'Function [ <%s> function %s ] {%s' . $parametersFormat . $returnTypeFormat . "\n}",
            self::sourceToString($functionReflection),
            $functionReflection->getName(),
            self::fileAndLinesToString($functionReflection),
            count($functionReflection->getParameters()),
            self::parametersToString($functionReflection),
            self::returnTypeToString($functionReflection),
        );
    }

    private static function sourceToString(ReflectionFunction $functionReflection): string
    {
        if ($functionReflection->isUserDefined()) {
            return 'user';
        }

        $extensionName = $functionReflection->getExtensionName();
        assert(is_string($extensionName));

        return sprintf('internal:%s', $extensionName);
    }

    private static function fileAndLinesToString(ReflectionFunction $functionReflection): string
    {
        if ($functionReflection->isInternal()) {
            return '';
        }

        $fileName = $functionReflection->getFileName();
        if ($fileName === null) {
            return '';
        }

        return sprintf("\n  @@ %s %d - %d", $fileName, $functionReflection->getStartLine(), $functionReflection->getEndLine());
    }

    private static function parametersToString(ReflectionFunction $functionReflection): string
    {
        return array_reduce($functionReflection->getParameters(), static fn (string $string, ReflectionParameter $parameterReflection): string => $string . "\n    " . ReflectionParameterStringCast::toString($parameterReflection), '');
    }

    private static function returnTypeToString(ReflectionFunction $methodReflection): string
    {
        $type = $methodReflection->getReturnType();

        if ($type === null) {
            return '';
        }

        return ReflectionTypeStringCast::toString($type);
    }
}
