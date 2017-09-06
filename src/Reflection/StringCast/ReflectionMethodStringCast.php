<?php
declare(strict_types=1);

namespace Roave\BetterReflection\Reflection\StringCast;

use Roave\BetterReflection\Reflection\Exception\MethodPrototypeNotFound;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use Roave\BetterReflection\Reflection\ReflectionParameter;

/**
 * @internal
 */
final class ReflectionMethodStringCast
{
    public static function toString(ReflectionMethod $methodReflection) : string
    {
        $parametersFormat = $methodReflection->getNumberOfParameters() > 0 ? "\n\n  - Parameters [%d] {%s\n  }" : '';

        return \sprintf(
            "Method [ <%s%s%s%s%s>%s%s%s %s method %s ] {%s{$parametersFormat}\n}",
            self::sourceToString($methodReflection),
            $methodReflection->isConstructor() ? ', ctor' : '',
            $methodReflection->isDestructor() ? ', dtor' : '',
            self::overwritesToString($methodReflection),
            self::prototypeToString($methodReflection),
            $methodReflection->isFinal() ? ' final' : '',
            $methodReflection->isStatic() ? ' static' : '',
            $methodReflection->isAbstract() ? ' abstract' : '',
            self::visibilityToString($methodReflection),
            $methodReflection->getName(),
            self::fileAndLinesToString($methodReflection),
            \count($methodReflection->getParameters()),
            self::parametersToString($methodReflection)
        );
    }

    private static function sourceToString(ReflectionMethod $methodReflection) : string
    {
        if ($methodReflection->isUserDefined()) {
            return 'user';
        }

        return \sprintf('internal:%s', $methodReflection->getExtensionName());
    }

    private static function overwritesToString(ReflectionMethod $methodReflection) : string
    {
        $parentClass = $methodReflection->getDeclaringClass()->getParentClass();

        if ( ! $parentClass) {
            return '';
        }

        if ( ! $parentClass->hasMethod($methodReflection->getName())) {
            return '';
        }

        return \sprintf(', overwrites %s', $parentClass->getName());
    }

    private static function prototypeToString(ReflectionMethod $methodReflection) : string
    {
        try {
            return \sprintf(', prototype %s', $methodReflection->getPrototype()->getDeclaringClass()->getName());
        } catch (MethodPrototypeNotFound $e) {
            return '';
        }
    }

    private static function visibilityToString(ReflectionMethod $methodReflection) : string
    {
        if ($methodReflection->isProtected()) {
            return 'protected';
        }

        if ($methodReflection->isPrivate()) {
            return 'private';
        }

        return 'public';
    }

    private static function fileAndLinesToString(ReflectionMethod $methodReflection) : string
    {
        if ($methodReflection->isInternal()) {
            return '';
        }

        return \sprintf("\n  @@ %s %d - %d", $methodReflection->getFileName(), $methodReflection->getStartLine(), $methodReflection->getEndLine());
    }

    private static function parametersToString(ReflectionMethod $methodReflection) : string
    {
        return \array_reduce($methodReflection->getParameters(), function (string $string, ReflectionParameter $parameterReflection) : string {
            return $string . "\n    " . ReflectionParameterStringCast::toString($parameterReflection);
        }, '');
    }
}
