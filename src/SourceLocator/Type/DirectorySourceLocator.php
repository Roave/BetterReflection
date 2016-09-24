<?php

namespace BetterReflection\SourceLocator\Type;

use BetterReflection\Identifier\Identifier;
use BetterReflection\Identifier\IdentifierType;
use BetterReflection\Reflector\Reflector;
use BetterReflection\SourceLocator\Exception\InvalidDirectory;

/**
 * This source locator loads all php files in an entire directory or multiple directories.
 */
class DirectorySourceLocator implements SourceLocator
{
    /**
     * @var AggregateSourceLocator
     */
    private $aggregatedSourceLocator;

    /**
     * @param $directories string[] directories to scan
     */
    public function __construct(array $directories)
    {
        $sourceLocators = [];
        foreach ($directories as $dir) {
            if (!is_string($dir)) {
                throw InvalidDirectory::fromNonStringValue($dir);
            } elseif (!is_dir($dir)) {
                throw InvalidDirectory::fromNonDirectory($dir);
            }
            $sourceLocators[] = new FileSystemIteratorSourceLocator(new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS));
        }
        $this->aggregatedSourceLocator = new AggregateSourceLocator($sourceLocators);
    }

    /**
     * {@inheritDoc}
     */
    public function locateIdentifier(Reflector $reflector, Identifier $identifier)
    {
        return $this->aggregatedSourceLocator->locateIdentifier($reflector, $identifier);
    }

    /**
     * {@inheritDoc}
     */
    public function locateIdentifiersByType(Reflector $reflector, IdentifierType $identifierType)
    {
        return $this->aggregatedSourceLocator->locateIdentifiersByType($reflector, $identifierType);
    }
}