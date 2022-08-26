<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Util;

use function class_exists;
use function interface_exists;
use function trait_exists;

/** @internal */
class ClassExistenceChecker
{
    /** @psalm-assert-if-true class-string $name */
    public static function exists(string $name): bool
    {
        return self::classExists($name) || self::interfaceExists($name) || self::traitExists($name);
    }

    public static function classExists(string $name): bool
    {
        return class_exists($name, false);
    }

    public static function interfaceExists(string $name): bool
    {
        return interface_exists($name, false);
    }

    public static function traitExists(string $name): bool
    {
        return trait_exists($name, false);
    }
}
