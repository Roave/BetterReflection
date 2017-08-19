<?php
declare(strict_types=1);

namespace Roave\BetterReflection\SourceLocator\Type;

use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\Identifier\IdentifierType;
use Roave\BetterReflection\Reflection\Reflection;
use Roave\BetterReflection\Reflector\Reflector;

class AggregateSourceLocator implements SourceLocator
{
    /**
     * @var SourceLocator[]
     */
    private $sourceLocators;

    /**
     * @param SourceLocator[] $sourceLocators
     */
    public function __construct(array $sourceLocators = [])
    {
        // This slightly confusing code simply type-checks the $sourceLocators
        // array by unpacking them and splatting them in the closure.
        $validator = function (SourceLocator ...$sourceLocator) {
            return $sourceLocator;
        };
        $this->sourceLocators = $validator(...$sourceLocators);
    }

    /**
     * {@inheritDoc}
     */
    public function locateIdentifier(Reflector $reflector, Identifier $identifier) : ?Reflection
    {
        foreach ($this->sourceLocators as $sourceLocator) {
            if ($located = $sourceLocator->locateIdentifier($reflector, $identifier)) {
                return $located;
            }
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function locateIdentifiersByType(Reflector $reflector, IdentifierType $identifierType) : array
    {
        return \array_merge(
            [],
            ...\array_map(function (SourceLocator $sourceLocator) use ($reflector, $identifierType) {
                return $sourceLocator->locateIdentifiersByType($reflector, $identifierType);
            }, $this->sourceLocators)
        );
    }
}
