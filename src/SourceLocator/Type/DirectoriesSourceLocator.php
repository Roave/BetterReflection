<?php

namespace BetterReflection\SourceLocator\Type;

use BetterReflection\Identifier\Identifier;
use BetterReflection\Identifier\IdentifierType;
use BetterReflection\Reflector\Reflector;
use BetterReflection\SourceLocator\Exception\InvalidDirectory;
use BetterReflection\SourceLocator\Exception\InvalidFileInfo;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * This source locator loads all php files in an entire directory or multiple directories.
 */
class DirectoriesSourceLocator implements SourceLocator
{
    /**
     * @var AggregateSourceLocator
     */
    private $aggregateSourceLocator;

    /**
     * @param string[] $directories directories to scan
     *
     * @throws InvalidDirectory
     * @throws InvalidFileInfo
     */
    public function __construct(array $directories)
    {
        $this->aggregateSourceLocator = new AggregateSourceLocator(array_map(
            function ($directory) {
                if (! is_string($directory)) {
                    throw InvalidDirectory::fromNonStringValue($directory);
                }

                if (! is_dir($directory)) {
                    throw InvalidDirectory::fromNonDirectory($directory);
                }

                return new SingleDirectorySourceLocator(new RecursiveIteratorIterator(new RecursiveDirectoryIterator(
                    $directory,
                    RecursiveDirectoryIterator::SKIP_DOTS
                )));
            },
            $directories
        ));
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
