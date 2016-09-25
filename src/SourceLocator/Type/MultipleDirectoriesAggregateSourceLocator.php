<?php

namespace BetterReflection\SourceLocator\Type;

use BetterReflection\Identifier\Identifier;
use BetterReflection\Identifier\IdentifierType;
use BetterReflection\Reflector\Reflector;
use BetterReflection\SourceLocator\Exception\InvalidDirectory;

/**
 * This source locator loads all php files in an entire directory or multiple directories.
 */
class MultipleDirectoriesAggregateSourceLocator implements SourceLocator
{
    /**
     * @var AggregateSourceLocator
     */
    private $aggregateSourceLocator;

    /**
     * @param string[] $directories directories to scan
     * @throws InvalidDirectory
     */
    public function __construct(array $directories)
    {
        $sourceLocators = [];
        foreach ($directories as $directory) {
            if (!is_string($directory)) {
                throw InvalidDirectory::fromNonStringValue($directory);
            }
            if (!is_dir($directory)) {
                throw InvalidDirectory::fromNonDirectory($directory);
            }
            $rdi = new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS);
            $sourceLocators[] = new SingleDirectorySourceLocator(new \RecursiveIteratorIterator($rdi));
        }
        $this->aggregateSourceLocator = new AggregateSourceLocator($sourceLocators);
    }

    /**
     * {@inheritDoc}
     */
    public function locateIdentifier(Reflector $reflector, Identifier $identifier)
    {
        return $this->aggregateSourceLocator->locateIdentifier($reflector, $identifier);
    }

    /**
     * {@inheritDoc}
     */
    public function locateIdentifiersByType(Reflector $reflector, IdentifierType $identifierType)
    {
        return $this->aggregateSourceLocator->locateIdentifiersByType($reflector, $identifierType);
    }
}
