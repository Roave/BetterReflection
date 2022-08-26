<?php

declare(strict_types=1);

namespace Roave\BetterReflection\SourceLocator\SourceStubber;

/** @internal */
class StubData
{
    public function __construct(private string $stub, private string|null $extensionName)
    {
    }

    public function getStub(): string
    {
        return $this->stub;
    }

    public function getExtensionName(): string|null
    {
        return $this->extensionName;
    }
}
