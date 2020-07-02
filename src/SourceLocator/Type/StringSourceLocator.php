<?php

declare(strict_types=1);

namespace Roave\BetterReflection\SourceLocator\Type;

use InvalidArgumentException;
use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\Exception\EmptyPhpSourceCode;
use Roave\BetterReflection\SourceLocator\Exception\InvalidFileLocation;
use Roave\BetterReflection\SourceLocator\Located\LocatedSource;

/**
 * This source locator simply parses the string given in the constructor as
 * valid PHP.
 *
 * Note that this source locator does NOT specify a filename, because we did
 * not load it from a file, so it will be null if you use this locator.
 */
class StringSourceLocator extends AbstractSourceLocator
{
    private string $source;

    /**
     * @throws EmptyPhpSourceCode
     */
    public function __construct(string $source, Locator $astLocator)
    {
        parent::__construct($astLocator);

        if (empty($source)) {
            // Whilst an empty string is still "valid" PHP code, there is no
            // point in us even trying to parse it because we won't find what
            // we are looking for, therefore this throws an exception
            throw new EmptyPhpSourceCode(
                'Source code string was empty',
            );
        }

        $this->source = $source;
    }

    /**
     * {@inheritDoc}
     *
     * @throws InvalidArgumentException
     * @throws InvalidFileLocation
     */
    protected function createLocatedSource(Identifier $identifier): ?LocatedSource
    {
        return new LocatedSource(
            $this->source,
            null,
        );
    }
}
