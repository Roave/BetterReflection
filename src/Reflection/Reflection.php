<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection;

/**
 * This interface is used internally by the Generic reflector in order to
 * ensure we are working with BetterReflection reflections.
 *
 * @internal
 *
 * @psalm-immutable
 */
interface Reflection
{
    /**
     * Get the name of the reflection (e.g. if this is a ReflectionClass this
     * will be the class name).
     */
    public function getName(): string;
}
