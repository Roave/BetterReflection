<?php

namespace BetterReflection\Reflector;

use BetterReflection\Identifier\Identifier;
use BetterReflection\Identifier\IdentifierType;
use BetterReflection\SourceLocator\Type\SourceLocator;
use BetterReflection\SourceLocator\Ast\Locator as AstLocator;
use BetterReflection\Reflection\Reflection;
use PhpParser\Parser;
use PhpParser\Lexer;
use PhpParser\Node;

class Generic
{
    /**
     * @var SourceLocator
     */
    private $sourceLocator;

    /**
     * @var Parser
     */
    private $parser;

    /**
     * @param AstLocator $astLocator
     */
    private $astLocator;

    public function __construct(SourceLocator $sourceLocator, Reflector $reflector)
    {
        $this->sourceLocator = $sourceLocator;
        $this->parser        = new Parser\Multiple([
            new Parser\Php7(new Lexer()),
            new Parser\Php5(new Lexer())
        ]);
        $this->astLocator = new AstLocator($reflector);
    }

    /**
     * Uses the SourceLocator given in the constructor to locate the $identifier
     * specified and returns the \Reflector.
     *
     * @param Identifier $identifier
     *
     * @return Reflection
     */
    public function reflect(Identifier $identifier)
    {
        $locator = $this->sourceLocator;

        if (! $potentiallyLocatedSource = $locator($identifier)) {
            throw Exception\IdentifierNotFound::fromIdentifier($identifier);
        }

        return $this->astLocator->findReflection($potentiallyLocatedSource, $identifier);
    }

    /**
     * Get all identifiers of a matching identifier type from a file.
     *
     * @param IdentifierType $identifierType
     * @return Reflection[]
     */
    public function getAllByIdentifierType(IdentifierType $identifierType)
    {
        $identifier = new Identifier('*', $identifierType);

        return $this->astLocator->findReflectionsOfType(
            $this->sourceLocator->__invoke($identifier),
            $identifierType
        );
    }
}
