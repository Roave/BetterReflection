<?php

namespace Roave\BetterReflection\SourceLocator\Type;

use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\Identifier\IdentifierType;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\Exception\InvalidFileInfo;

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
                    if (! ($item->isFile() && pathinfo($item->getRealPath(), \PATHINFO_EXTENSION) === 'php')) {
                        return null;
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
