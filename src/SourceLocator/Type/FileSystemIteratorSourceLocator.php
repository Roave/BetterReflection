<?php

namespace BetterReflection\SourceLocator\Type;

use BetterReflection\Identifier\Identifier;
use BetterReflection\Identifier\IdentifierType;
use BetterReflection\Reflector\Reflector;
use BetterReflection\SourceLocator\Exception\InvalidFileInfo;

/**
 * This source locator loads all php files from \FileSystemIterator
 */
class FileSystemIteratorSourceLocator implements SourceLocator
{
    /**
     * Don't touch this variable directly
     * @var AggregateSourceLocator
     */
    private $aggregatedSourceLocator;

    /**
     * @var \Iterator
     */
    private $fileSystemIterator;

    public function __construct(\Iterator $fileInfoIterator)
    {
        foreach ($fileInfoIterator as $fileInfo) {
            if (!$fileInfo instanceof \SplFileInfo) {
                throw InvalidFileInfo::fromNonSplFileInfo($fileInfo);
            }
        }
        $this->fileSystemIterator = $fileInfoIterator;
    }

    /**
     * Get a AggregateSourceLocator, create it if null.
     * @return AggregateSourceLocator
     */
    private function getAggregatedSourceLocator()
    {
        return $this->aggregatedSourceLocator ?
            $this->aggregatedSourceLocator : new AggregateSourceLocator($this->scan());
    }

    /**
     * scan target directory and resulted as SourceLocator[]
     * @return SourceLocator[]
     */
    private function scan()
    {
        $sourceLocators = [];
        foreach ($this->fileSystemIterator as $item) {
            /* @var $item \SplFileInfo */
            if ($item->isFile() && pathinfo($item->getRealPath(), PATHINFO_EXTENSION) == 'php') {
                $sourceLocators[] = new SingleFileSourceLocator($item->getRealPath());
            }
        }
        return $sourceLocators;
    }

    /**
     * {@inheritDoc}
     */
    public function locateIdentifier(Reflector $reflector, Identifier $identifier)
    {
        return $this->getAggregatedSourceLocator()->locateIdentifier($reflector, $identifier);
    }

    /**
     * {@inheritDoc}
     */
    public function locateIdentifiersByType(Reflector $reflector, IdentifierType $identifierType)
    {
        return $this->getAggregatedSourceLocator()->locateIdentifiersByType($reflector, $identifierType);
    }
}
