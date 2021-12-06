<?php

declare(strict_types=1);

namespace Roave\BetterReflection\SourceLocator\Type;

use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\Identifier\IdentifierType;
use Roave\BetterReflection\Reflection\Reflection;
use Roave\BetterReflection\Reflector\Reflector;

use function array_map;
use function array_merge;

class AggregateSourceLocator implements SourceLocator
{
    /** @var list<SourceLocator> */
    private array $sourceLocators;

    /**
     * @param list<SourceLocator> $sourceLocators
     */
    public function __construct(array $sourceLocators = [])
    {
        // This slightly confusing code simply type-checks the $sourceLocators
        // array by unpacking them and splatting them in the closure.
        $validator            = static fn (SourceLocator ...$sourceLocator): array => $sourceLocator;
        $this->sourceLocators = $validator(...$sourceLocators);
    }

    public function locateIdentifier(Reflector $reflector, Identifier $identifier): ?Reflection
    {
        foreach ($this->sourceLocators as $sourceLocator) {
            $located = $sourceLocator->locateIdentifier($reflector, $identifier);

            if ($located) {
                return $located;
            }
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function locateIdentifiersByType(Reflector $reflector, IdentifierType $identifierType): array
    {
        return array_merge(
            [],
            ...array_map(static fn (SourceLocator $sourceLocator): array => $sourceLocator->locateIdentifiersByType($reflector, $identifierType), $this->sourceLocators),
        );
    }
}
