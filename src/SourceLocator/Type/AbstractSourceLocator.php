<?php

namespace Roave\BetterReflection\SourceLocator\Type;

use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\Identifier\IdentifierType;
use Roave\BetterReflection\Reflector\Exception\IdentifierNotFound;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\Ast\Locator as AstLocator;
use Roave\BetterReflection\SourceLocator\Located\LocatedSource;

abstract class AbstractSourceLocator implements SourceLocator
{
    /**
     * @var AstLocator
     */
    private $astLocator;

    /**
     * Children should implement this method and return a LocatedSource object
     * which contains the source and the file from which it was located.
     *
     * @example
     *   return new LocatedSource(['<?php class Foo {}', null]);
     *   return new LocatedSource([file_get_contents('Foo.php'), 'Foo.php']);
     *
     * @param Identifier $identifier
     * @return LocatedSource
     */
    abstract protected function createLocatedSource(Identifier $identifier);

    public function __construct(AstLocator $astLocator = null)
    {
        $this->astLocator = $astLocator ?: new AstLocator();
    }

    /**
     * {@inheritDoc}
     */
    public function locateIdentifier(Reflector $reflector, Identifier $identifier)
    {
        if (!($locatedSource = $this->createLocatedSource($identifier))) {
            return null;
        }

        try {
            return $this->astLocator->findReflection($reflector, $locatedSource, $identifier);
        } catch (IdentifierNotFound $exception) {
            return null;
        }
    }

    /**
     * {@inheritDoc}
     */
    final public function locateIdentifiersByType(Reflector $reflector, IdentifierType $identifierType)
    {
        if (!($locatedSource = $this->createLocatedSource(new Identifier('*', $identifierType)))) {
            return [];
        }

        return $this->astLocator->findReflectionsOfType(
            $reflector,
            $locatedSource,
            $identifierType
        );
    }
}
