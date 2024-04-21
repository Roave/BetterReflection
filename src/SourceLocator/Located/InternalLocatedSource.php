<?php

declare(strict_types=1);

namespace Roave\BetterReflection\SourceLocator\Located;

/**
 * @internal
 *
 * @psalm-immutable
 */
final class InternalLocatedSource extends LocatedSource
{
    /** @param non-empty-string $extensionName */
    public function __construct(string $source, string $name, private string $extensionName)
    {
        parent::__construct($source, $name);
    }

    public function isInternal(): bool
    {
        return true;
    }

    /** @return non-empty-string|null */
    public function getExtensionName(): string|null
    {
        return $this->extensionName;
    }
}
