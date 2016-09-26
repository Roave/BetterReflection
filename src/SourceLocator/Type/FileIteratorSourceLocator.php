<?php

namespace BetterReflection\SourceLocator\Type;

use BetterReflection\Identifier\Identifier;
use BetterReflection\Identifier\IdentifierType;
use BetterReflection\Reflector\Reflector;
use BetterReflection\SourceLocator\Exception\InvalidFileInfo;

/**
 * This source locator loads all php files from \FileSystemIterator
 */
class FileIteratorSourceLocator implements SourceLocator
{
    /**
     * @var AggregateSourceLocator|null
     */
    private $aggregateSourceLocator;

    /**
     * @var \Iterator|\SplFileInfo[]
     */
    private $fileSystemIterator;

    /**
     * @param \Iterator|\SplFileInfo[] $fileInfoIterator note: only \SplFileInfo allowed in this iterator
     *
     * @throws InvalidFileInfo In case of iterator not contains only SplFileInfo
     */
    public function __construct(\Iterator $fileInfoIterator)
    {
        foreach ($fileInfoIterator as $fileInfo) {
            if (! $fileInfo instanceof \SplFileInfo) {
                throw InvalidFileInfo::fromNonSplFileInfo($fileInfo);
            }
        }

        $this->fileSystemIterator = $fileInfoIterator;
    }

    /**
     * @return AggregateSourceLocator
     */
    private function getAggregatedSourceLocator()
    {
        return $this->aggregateSourceLocator ?
            $this->aggregateSourceLocator : new AggregateSourceLocator(array_values(array_filter(array_map(
                function (\SplFileInfo $item) {
                    if (! ($item->isFile() && pathinfo($item->getRealPath(), \PATHINFO_EXTENSION) == 'php')) {
                        return;
                    }

                    return new SingleFileSourceLocator($item->getRealPath());
                },
                iterator_to_array($this->fileSystemIterator)
            ))));
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
