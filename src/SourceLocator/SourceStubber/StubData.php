<?php

declare(strict_types=1);

namespace Roave\BetterReflection\SourceLocator\SourceStubber;

/**
 * @internal
 */
class StubData
{
    private string $stub;

    private ?string $extensionName;

    public function __construct(string $stub, ?string $extensionName)
    {
        $this->stub          = $stub;
        $this->extensionName = $extensionName;
    }

    public function getStub(): string
    {
        return $this->stub;
    }

    public function getExtensionName(): ?string
    {
        return $this->extensionName;
    }
}
