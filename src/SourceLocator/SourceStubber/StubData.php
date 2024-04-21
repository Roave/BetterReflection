<?php

declare(strict_types=1);

namespace Roave\BetterReflection\SourceLocator\SourceStubber;

/** @internal */
final class StubData
{
    /** @param non-empty-string|null $extensionName */
    public function __construct(private string $stub, private string|null $extensionName)
    {
    }

    public function getStub(): string
    {
        return $this->stub;
    }

    /** @return non-empty-string|null */
    public function getExtensionName(): string|null
    {
        return $this->extensionName;
    }
}
