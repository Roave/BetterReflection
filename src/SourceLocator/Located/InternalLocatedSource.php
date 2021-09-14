<?php

declare(strict_types=1);

namespace Roave\BetterReflection\SourceLocator\Located;

class InternalLocatedSource extends LocatedSource
{
    /**
     * {@inheritDoc}
     */
    public function __construct(string $source, private string $extensionName)
    {
        parent::__construct($source, null);
    }

    public function isInternal(): bool
    {
        return true;
    }

    public function getExtensionName(): ?string
    {
        return $this->extensionName;
    }
}
