<?php

namespace BetterReflection\SourceLocator;

use BetterReflection\Identifier\Identifier;

/**
 * This source locator simply parses the string given in the constructor as
 * valid PHP.
 *
 * Note that this source locator does NOT specify a filename, because we did
 * not load it from a file, so it will be null if you use this locator.
 */
class StringSourceLocator implements SourceLocator
{
    /**
     * @var string
     */
    private $source;

    public function __construct($source)
    {
        $this->source = (string)$source;

        if (empty($this->source)) {
            throw new \InvalidArgumentException(
                'Source code string was empty'
            );
        }
    }

    /**
     * @param Identifier $identifier
     * @return LocatedSource
     */
    public function __invoke(Identifier $identifier)
    {
        return new LocatedSource($this->source, null);
    }
}
