<?php
declare(strict_types=1);

namespace Rector\BetterReflection\SourceLocator\Type;

use Rector\BetterReflection\Identifier\Identifier;
use Rector\BetterReflection\Identifier\IdentifierType;
use Rector\BetterReflection\Reflection\Reflection;
use Rector\BetterReflection\Reflector\Exception\IdentifierNotFound;
use Rector\BetterReflection\Reflector\Reflector;
use Rector\BetterReflection\SourceLocator\Ast\Locator as AstLocator;
use Rector\BetterReflection\SourceLocator\Located\LocatedSource;

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
     *   return new LocatedSource([\file_get_contents('Foo.php'), 'Foo.php']);
     *
     * @param Identifier $identifier
     * @return LocatedSource|null
     */
    abstract protected function createLocatedSource(Identifier $identifier) : ?LocatedSource;

    public function __construct(AstLocator $astLocator)
    {
        $this->astLocator = $astLocator;
    }

    /**
     * {@inheritDoc}
     * @throws \Rector\BetterReflection\SourceLocator\Ast\Exception\ParseToAstFailure
     */
    public function locateIdentifier(Reflector $reflector, Identifier $identifier) : ?Reflection
    {
        if ( ! ($locatedSource = $this->createLocatedSource($identifier))) {
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
     * @throws \Rector\BetterReflection\SourceLocator\Ast\Exception\ParseToAstFailure
     */
    final public function locateIdentifiersByType(Reflector $reflector, IdentifierType $identifierType) : array
    {
        if ( ! ($locatedSource = $this->createLocatedSource(new Identifier(Identifier::WILDCARD, $identifierType)))) {
            return [];
        }

        return $this->astLocator->findReflectionsOfType(
            $reflector,
            $locatedSource,
            $identifierType
        );
    }
}
