<?php
declare(strict_types=1);

namespace Rector\BetterReflection\SourceLocator\Type;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Rector\BetterReflection\Identifier\Identifier;
use Rector\BetterReflection\Identifier\IdentifierType;
use Rector\BetterReflection\Reflection\Reflection;
use Rector\BetterReflection\Reflector\Reflector;
use Rector\BetterReflection\SourceLocator\Ast\Locator;
use Rector\BetterReflection\SourceLocator\Exception\InvalidDirectory;
use Rector\BetterReflection\SourceLocator\Exception\InvalidFileInfo;

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
    public function __construct(array $directories, Locator $astLocator)
    {
        $this->aggregateSourceLocator = new AggregateSourceLocator(\array_values(\array_map(
            function ($directory) use ($astLocator) : FileIteratorSourceLocator {
                if ( ! \is_string($directory)) {
                    throw InvalidDirectory::fromNonStringValue($directory);
                }

                if ( ! \is_dir($directory)) {
                    throw InvalidDirectory::fromNonDirectory($directory);
                }

                return new FileIteratorSourceLocator(
                    new RecursiveIteratorIterator(new RecursiveDirectoryIterator(
                        $directory,
                        RecursiveDirectoryIterator::SKIP_DOTS
                    )),
                    $astLocator
                );
            },
            $directories
        )));
    }

    /**
     * {@inheritDoc}
     */
    public function locateIdentifier(Reflector $reflector, Identifier $identifier) : ?Reflection
    {
        return $this->aggregateSourceLocator->locateIdentifier($reflector, $identifier);
    }

    /**
     * {@inheritDoc}
     */
    public function locateIdentifiersByType(Reflector $reflector, IdentifierType $identifierType) : array
    {
        return $this->aggregateSourceLocator->locateIdentifiersByType($reflector, $identifierType);
    }
}
