<?php

declare(strict_types=1);

namespace Roave\BetterReflection\SourceLocator\SourceStubber;

use ReflectionClass as CoreReflectionClass;

/**
 * @internal
 */
interface SourceStubber
{
    /**
     * Generates stub for given class. Returns null when it cannot generate the stub.
     */
    public function generateClassStub(CoreReflectionClass $classReflection) : ?string;
}
