<?php

declare(strict_types=1);

namespace Roave\BetterReflection\SourceLocator\Type\Composer;

use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\Identifier\IdentifierType;
use Roave\BetterReflection\Reflection\Reflection;
use Roave\BetterReflection\Reflector\Exception\IdentifierNotFound;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\Exception\InvalidFileLocation;
use Roave\BetterReflection\SourceLocator\FileChecker;
use Roave\BetterReflection\SourceLocator\Located\LocatedSource;
use Roave\BetterReflection\SourceLocator\Type\Composer\Psr\PsrAutoloaderMapping;
use Roave\BetterReflection\SourceLocator\Type\DirectoriesSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\SourceLocator;

use function file_get_contents;

final class PsrAutoloaderLocator implements SourceLocator
{
    public function __construct(private PsrAutoloaderMapping $mapping, private Locator $astLocator)
    {
    }

    public function locateIdentifier(Reflector $reflector, Identifier $identifier): Reflection|null
    {
        foreach ($this->mapping->resolvePossibleFilePaths($identifier) as $file) {
            try {
                FileChecker::assertReadableFile($file);

                return $this->astLocator->findReflection(
                    $reflector,
                    new LocatedSource(
                        file_get_contents($file),
                        $identifier->getName(),
                        $file,
                    ),
                    $identifier,
                );
            } catch (InvalidFileLocation) {
                // Ignore
            } catch (IdentifierNotFound) {
                // on purpose - autoloading is allowed to fail, and silently-failing autoloaders are normal/endorsed
            }
        }

        return null;
    }

    /**
     * Find all identifiers of a type
     *
     * @return list<Reflection>
     */
    public function locateIdentifiersByType(Reflector $reflector, IdentifierType $identifierType): array
    {
        return (new DirectoriesSourceLocator(
            $this->mapping->directories(),
            $this->astLocator,
        ))->locateIdentifiersByType($reflector, $identifierType);
    }
}
