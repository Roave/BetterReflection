<?php

declare(strict_types=1);

namespace Roave\BetterReflection\SourceLocator\Type\Composer;

use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\Identifier\IdentifierType;
use Roave\BetterReflection\Reflection\Reflection;
use Roave\BetterReflection\Reflector\Exception\IdentifierNotFound;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\Located\LocatedSource;
use Roave\BetterReflection\SourceLocator\Type\Composer\Psr\PsrAutoloaderMapping;
use Roave\BetterReflection\SourceLocator\Type\DirectoriesSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\SourceLocator;
use function file_exists;
use function file_get_contents;

final class PsrAutoloaderLocator implements SourceLocator
{
    /** @var PsrAutoloaderMapping */
    private $mapping;

    /** @var Locator */
    private $astLocator;

    public function __construct(PsrAutoloaderMapping $mapping, Locator $astLocator)
    {
        $this->mapping    = $mapping;
        $this->astLocator = $astLocator;
    }

    public function locateIdentifier(Reflector $reflector, Identifier $identifier) : ?Reflection
    {
        foreach ($this->mapping->resolvePossibleFilePaths($identifier) as $file) {
            if (! file_exists($file)) {
                continue;
            }

            try {
                return $this->astLocator->findReflection(
                    $reflector,
                    new LocatedSource(
                        file_get_contents($file),
                        $file
                    ),
                    $identifier
                );
            } catch (IdentifierNotFound $exception) {
                // on purpose - autoloading is allowed to fail, and silently-failing autoloaders are normal/endorsed
            }
        }

        return null;
    }

    /**
     * Find all identifiers of a type
     *
     * @return Reflection[]
     */
    public function locateIdentifiersByType(Reflector $reflector, IdentifierType $identifierType) : array
    {
        return (new DirectoriesSourceLocator(
            $this->mapping->directories(),
            $this->astLocator
        ))->locateIdentifiersByType($reflector, $identifierType);
    }
}
