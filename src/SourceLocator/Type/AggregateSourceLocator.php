<?php

namespace BetterReflection\SourceLocator\Type;

use BetterReflection\Identifier\Identifier;
use BetterReflection\Reflector\ClassReflector;
use BetterReflection\SourceLocator\Located\DefiniteLocatedSource;
use BetterReflection\SourceLocator\Located\PotentiallyLocatedSource;
use BetterReflection\SourceLocator\Ast\Locator as AstLocator;

class AggregateSourceLocator implements SourceLocator
{
    /**
     * @var SourceLocator[]
     */
    private $sourceLocators;

    /**
     * @param AstLocator $astLocator
     */
    private $astLocator;

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
        $this->astLocator = new AstLocator(new ClassReflector($this));
    }

    /**
     * {@inheritDoc}
     */
    public function __invoke(Identifier $identifier)
    {
        foreach ($this->sourceLocators as $sourceLocator) {
            $located = $sourceLocator($identifier);

            if (($located instanceof PotentiallyLocatedSource && $this->astLocator->hasIdentifier($located, $identifier))
                || $located instanceof DefiniteLocatedSource) {
                return $located;
            }
        }

        return null;
    }
}
