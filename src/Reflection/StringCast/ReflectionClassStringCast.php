<?php
declare(strict_types=1);

namespace Rector\BetterReflection\Reflection\StringCast;

use Rector\BetterReflection\Reflection\ReflectionClass;
use Rector\BetterReflection\Reflection\ReflectionClassConstant;
use Rector\BetterReflection\Reflection\ReflectionMethod;
use Rector\BetterReflection\Reflection\ReflectionObject;
use Rector\BetterReflection\Reflection\ReflectionProperty;

/**
 * @internal
 */
final class ReflectionClassStringCast
{
    public static function toString(ReflectionClass $classReflection) : string
    {
        $isObject = $classReflection instanceof ReflectionObject;

        $format  = "%s [ <%s> %s%s%s %s%s%s ] {\n";
        $format .= "%s\n";
        $format .= "  - Constants [%d] {%s\n  }\n\n";
        $format .= "  - Static properties [%d] {%s\n  }\n\n";
        $format .= "  - Static methods [%d] {%s\n  }\n\n";
        $format .= "  - Properties [%d] {%s\n  }\n\n";
        $format .= ($isObject ? "  - Dynamic properties [%d] {%s\n  }\n\n" : '%s%s');
        $format .= "  - Methods [%d] {%s\n  }\n";
        $format .= "}\n";

        $type = self::typeToString($classReflection);

        $constants         = $classReflection->getReflectionConstants();
        $staticProperties  = self::getStaticProperties($classReflection);
        $staticMethods     = self::getStaticMethods($classReflection);
        $defaultProperties = self::getDefaultProperties($classReflection);
        $dynamicProperties = self::getDynamicProperties($classReflection);
        $methods           = self::getMethods($classReflection);

        return \sprintf(
            $format,
            $isObject ? 'Object of class' : $type,
            self::sourceToString($classReflection),
            $classReflection->isFinal() ? 'final ' : '',
            $classReflection->isAbstract() ? 'abstract ' : '',
            \strtolower($type),
            $classReflection->getName(),
            self::extendsToString($classReflection),
            self::implementsToString($classReflection),
            self::fileAndLinesToString($classReflection),
            \count($constants),
            self::constantsToString($constants),
            \count($staticProperties),
            self::propertiesToString($staticProperties),
            \count($staticMethods),
            self::methodsToString($classReflection, $staticMethods),
            \count($defaultProperties),
            self::propertiesToString($defaultProperties),
            $isObject ? \count($dynamicProperties) : '',
            $isObject ? self::propertiesToString($dynamicProperties) : '',
            \count($methods),
            self::methodsToString($classReflection, $methods, 2)
        );
    }

    private static function typeToString(ReflectionClass $classReflection) : string
    {
        if ($classReflection->isInterface()) {
            return 'Interface';
        }

        if ($classReflection->isTrait()) {
            return 'Trait';
        }

        return 'Class';
    }

    private static function sourceToString(ReflectionClass $classReflection) : string
    {
        if ($classReflection->isUserDefined()) {
            return 'user';
        }

        return \sprintf('internal:%s', $classReflection->getExtensionName());
    }

    private static function extendsToString(ReflectionClass $classReflection) : string
    {
        if ( ! $classReflection->getParentClass()) {
            return '';
        }

        return ' extends ' . $classReflection->getParentClass()->getName();
    }

    private static function implementsToString(ReflectionClass $classReflection) : string
    {
        $interfaceNames = $classReflection->getInterfaceNames();

        if ( ! $interfaceNames) {
            return '';
        }

        return ' implements ' . \implode(', ', $interfaceNames);
    }

    private static function fileAndLinesToString(ReflectionClass $classReflection) : string
    {
        if ($classReflection->isInternal()) {
            return '';
        }

        return \sprintf("  @@ %s %d-%d\n", $classReflection->getFileName(), $classReflection->getStartLine(), $classReflection->getEndLine());
    }

    /**
     * @param ReflectionClassConstant[] $constants

     * @return string
     */
    private static function constantsToString(array $constants) : string
    {
        if ( ! $constants) {
            return '';
        }

        return self::itemsToString(\array_map(function (ReflectionClassConstant $constantReflection) : string {
            return \trim(ReflectionClassConstantStringCast::toString($constantReflection));
        }, $constants));
    }

    /**
     * @param ReflectionProperty[] $properties

     * @return string
     */
    private static function propertiesToString(array $properties) : string
    {
        if ( ! $properties) {
            return '';
        }

        return self::itemsToString(\array_map(function (ReflectionProperty $propertyReflection) : string {
            return ReflectionPropertyStringCast::toString($propertyReflection);
        }, $properties));
    }

    /**
     * @param ReflectionClass $classReflection
     * @param ReflectionMethod[] $methods
     * @param int $emptyLinesAmongItems
     *
     * @return string
     */
    private static function methodsToString(ReflectionClass $classReflection, array $methods, int $emptyLinesAmongItems = 1) : string
    {
        if ( ! $methods) {
            return '';
        }

        return self::itemsToString(\array_map(function (ReflectionMethod $method) use ($classReflection) : string {
            return ReflectionMethodStringCast::toString($method, $classReflection);
        }, $methods), $emptyLinesAmongItems);
    }

    /**
     * @param string[] $items
     * @param int $emptyLinesAmongItems

     * @return string
     */
    private static function itemsToString(array $items, int $emptyLinesAmongItems = 1) : string
    {
        $string = \implode(\str_repeat("\n", $emptyLinesAmongItems), $items);
        return "\n" . \preg_replace('/(^|\n)(?!\n)/', '\1' . self::indent(), $string);
    }

    private static function indent() : string
    {
        return \str_repeat(' ', 4);
    }

    /**
     * @param ReflectionClass $classReflection

     * @return ReflectionProperty[]
     */
    private static function getStaticProperties(ReflectionClass $classReflection) : array
    {
        return \array_filter($classReflection->getProperties(), function (ReflectionProperty $propertyReflection) : bool {
            return $propertyReflection->isStatic();
        });
    }

    /**
     * @param ReflectionClass $classReflection

     * @return ReflectionMethod[]
     */
    private static function getStaticMethods(ReflectionClass $classReflection) : array
    {
        return \array_filter($classReflection->getMethods(), function (ReflectionMethod $methodReflection) : bool {
            return $methodReflection->isStatic();
        });
    }

    /**
     * @param ReflectionClass $classReflection

     * @return ReflectionProperty[]
     */
    private static function getDefaultProperties(ReflectionClass $classReflection) : array
    {
        return \array_filter($classReflection->getProperties(), function (ReflectionProperty $propertyReflection) : bool {
            return ! $propertyReflection->isStatic() && $propertyReflection->isDefault();
        });
    }

    /**
     * @param ReflectionClass $classReflection

     * @return ReflectionProperty[]
     */
    private static function getDynamicProperties(ReflectionClass $classReflection) : array
    {
        return \array_filter($classReflection->getProperties(), function (ReflectionProperty $propertyReflection) : bool {
            return ! $propertyReflection->isStatic() && ! $propertyReflection->isDefault();
        });
    }

    /**
     * @param ReflectionClass $classReflection

     * @return ReflectionMethod[]
     */
    private static function getMethods(ReflectionClass $classReflection) : array
    {
        return \array_filter($classReflection->getMethods(), function (ReflectionMethod $methodReflection) : bool {
            return ! $methodReflection->isStatic();
        });
    }
}
