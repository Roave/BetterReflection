<?php

namespace BetterReflection\SourceLocator;

use BetterReflection\Identifier\Identifier;

class AggregateSourceLocator implements SourceLocator
{
    /**
     * @var SourceLocator[]
     */
    private $sourceLocators;

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
     * Generator to invoke multiple source locators
     *
     * @param Identifier $identifier
     * @return LocatedSource
     */
    public function __invoke(Identifier $identifier)
    {
        foreach ($this->sourceLocators as $sourceLocator) {
            if ($sourceLocator instanceof self) {
                foreach ($sourceLocator->__invoke($identifier) as $value) {
                    yield $value;
                }
                continue;
            }
            yield $sourceLocator($identifier);
        }
    }
}
