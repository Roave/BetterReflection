<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection;

use PropertyHookType as CoreReflectionPropertyHookType;

/**
 * It's a copy of native PropertyHookType enum.
 *
 * It's used to avoid dependency on native enum in better ReflectionProperty class.
 * Thanks to this we can support PHP version < 8.4.
 *
 * @see CoreReflectionPropertyHookType
 */
enum ReflectionPropertyHookType: string
{
    case Get = 'get';
    case Set = 'set';

    public static function fromCoreReflectionPropertyHookType(CoreReflectionPropertyHookType $hookType): self
    {
        return match ($hookType) {
            CoreReflectionPropertyHookType::Get => self::Get,
            CoreReflectionPropertyHookType::Set => self::Set,
        };
    }
}
