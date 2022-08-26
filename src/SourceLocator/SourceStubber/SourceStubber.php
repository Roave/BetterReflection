<?php

declare(strict_types=1);

namespace Roave\BetterReflection\SourceLocator\SourceStubber;

/** @internal */
interface SourceStubber
{
    /**
     * Generates stub for given class. Returns null when it cannot generate the stub.
     *
     * @param class-string|trait-string $className
     */
    public function generateClassStub(string $className): StubData|null;

    /**
     * Generates stub for given function. Returns null when it cannot generate the stub.
     */
    public function generateFunctionStub(string $functionName): StubData|null;

    /**
     * Generates stub for given constant. Returns null when it cannot generate the stub.
     */
    public function generateConstantStub(string $constantName): StubData|null;
}
