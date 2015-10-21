<?php

namespace BetterReflection\SourceLocator\Type;
use BetterReflection\Identifier\Identifier;
use BetterReflection\Identifier\IdentifierType;
use BetterReflection\Reflector\Exception\IdentifierNotFound;
use BetterReflection\Reflector\Reflector;
use BetterReflection\SourceLocator\Ast\Locator as AstLocator;
use BetterReflection\SourceLocator\Located\LocatedSource;

/**
 * Use PHP's built in autoloader to locate a class, without actually loading.
 *
 * There are some prerequisites...
 *   - we expect the autoloader to load classes from a file (i.e. using require/include)
 */
abstract class AbstractSourceLocator implements SourceLocator
{
    /**
     * Children should implement this method return an array with two values.
     * The first key should be the code itself, and the second key the filename
     * containing the key, or null.
     *
     * @example
     *   return ['<?php class Foo {}', null];
     *   return [file_get_contents('Foo.php'), 'Foo.php'];
     *
     * @param Identifier $identifier
     * @return LocatedSource
     */
    abstract protected function createLocatedSource(Identifier $identifier);

    /**
     * {@inheritDoc}
     */
    public function locateIdentifier(Reflector $reflector, Identifier $identifier)
    {
        if (!($locatedSource = $this->createLocatedSource($identifier))) {
            return null;
        }

        try {
            return (new AstLocator())->findReflection($reflector, $locatedSource, $identifier);
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

        return (new AstLocator())->findReflectionsOfType(
            $reflector,
            $locatedSource,
            $identifierType
        );
    }
}
