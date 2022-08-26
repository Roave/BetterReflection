<?php

declare(strict_types=1);

namespace Roave\BetterReflection\SourceLocator\Located;

/** @internal */
class InternalLocatedSource extends LocatedSource
{
    public function __construct(string $source, string $name, private string $extensionName)
    {
        parent::__construct($source, $name);
    }

    public function isInternal(): bool
    {
        return true;
    }

    public function getExtensionName(): string|null
    {
        return $this->extensionName;
    }
}
