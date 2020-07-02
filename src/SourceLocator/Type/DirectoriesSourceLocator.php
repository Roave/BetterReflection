<?php

declare(strict_types=1);

namespace Roave\BetterReflection\SourceLocator\Type;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\Identifier\IdentifierType;
use Roave\BetterReflection\Reflection\Reflection;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\Exception\InvalidDirectory;
use Roave\BetterReflection\SourceLocator\Exception\InvalidFileInfo;

use function array_map;
use function array_values;
use function is_dir;
use function is_string;

/**
 * This source locator recursively loads all php files in an entire directory or multiple directories.
 */
class DirectoriesSourceLocator implements SourceLocator
{
    private AggregateSourceLocator $aggregateSourceLocator;

    /**
     * @param string[] $directories directories to scan
     *
     * @throws InvalidDirectory
     * @throws InvalidFileInfo
     */
    public function __construct(array $directories, Locator $astLocator)
    {
        $this->aggregateSourceLocator = new AggregateSourceLocator(array_values(array_map(
            static function ($directory) use ($astLocator): FileIteratorSourceLocator {
                if (! is_string($directory)) {
                    throw InvalidDirectory::fromNonStringValue($directory);
                }

                if (! is_dir($directory)) {
                    throw InvalidDirectory::fromNonDirectory($directory);
                }

                return new FileIteratorSourceLocator(
                    new RecursiveIteratorIterator(new RecursiveDirectoryIterator(
                        $directory,
                        RecursiveDirectoryIterator::SKIP_DOTS,
                    )),
                    $astLocator,
                );
            },
            $directories,
        )));
    }

    public function locateIdentifier(Reflector $reflector, Identifier $identifier): ?Reflection
    {
        return $this->aggregateSourceLocator->locateIdentifier($reflector, $identifier);
    }

    /**
     * {@inheritDoc}
     */
    public function locateIdentifiersByType(Reflector $reflector, IdentifierType $identifierType): array
    {
        return $this->aggregateSourceLocator->locateIdentifiersByType($reflector, $identifierType);
    }
}
