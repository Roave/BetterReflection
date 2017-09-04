<?php
declare(strict_types=1);

namespace Roave\BetterReflection\SourceLocator\Located;

/**
 * {@inheritDoc}
 */
class InternalLocatedSource extends LocatedSource
{
    /**
     * @var string
     */
    private $extensionName;

    /**
     * {@inheritDoc}
     */
    public function __construct(string $source, string $extensionName)
    {
        parent::__construct($source, null);

        $this->extensionName = $extensionName;
    }

    /**
     * {@inheritDoc}
     */
    public function isInternal() : bool
    {
        return true;
    }

    public function getExtensionName() : ?string
    {
        return $this->extensionName;
    }
}
