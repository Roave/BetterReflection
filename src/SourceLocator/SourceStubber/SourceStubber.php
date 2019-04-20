<?php

declare(strict_types=1);

namespace Roave\BetterReflection\SourceLocator\SourceStubber;

use ReflectionClass as CoreReflectionClass;
use ReflectionFunction as CoreReflectionFunction;

/**
 * @internal
 */
interface SourceStubber
{
    /**
     * Generates stub for given class. Returns null when it cannot generate the stub.
     */
    public function generateClassStub(CoreReflectionClass $classReflection) : ?string;

    /**
     * Generates stub for given function. Returns null when it cannot generate the stub.
     */
    public function generateFunctionStub(CoreReflectionFunction $functionReflection) : ?string;
}
