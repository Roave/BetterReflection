<?php

namespace BetterReflection\SourceLocator\Type;

use BetterReflection\Identifier\Identifier;
use BetterReflection\Identifier\IdentifierType;
use BetterReflection\Reflector\Reflector;

/**
 * This source locator loads all php files from \FileSystemIterator
 */
class FileSystemIteratorSourceLocator implements  SourceLocator
{
    /**
     * @var AggregateSourceLocator
     */
    private $_aggregatedSourceLocator;

    /**
     * @var \FilesystemIterator
     */
    private $fileSystemIterator;

    public function __construct(\FilesystemIterator $filesystemIterator)
    {
        $this->fileSystemIterator = $filesystemIterator;
    }

    private function getAggregatedSourceLocator()
    {
        if (null==$this->_aggregatedSourceLocator) {
            $sourceLocators = $this->scan();
            $this->_aggregatedSourceLocator = new AggregateSourceLocator($sourceLocators);
        }
        return $this->_aggregatedSourceLocator;
    }

    /**
     * scan target directory and resulted as SourceLocator[]
     * @return SourceLocator[]
     */
    private function scan()
    {
        $sourceLocators = [];
        foreach ( new \RecursiveIteratorIterator($this->fileSystemIterator) as $item ) {
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