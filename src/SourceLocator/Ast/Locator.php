<?php

namespace BetterReflection\SourceLocator\Ast;

use BetterReflection\Identifier\Identifier;
use BetterReflection\Identifier\IdentifierType;
use BetterReflection\SourceLocator\Ast\Strategy\NodeToReflection;
use BetterReflection\SourceLocator\Located\LocatedSource;
use BetterReflection\Reflection\Reflection;
use BetterReflection\Reflector\Reflector;
use BetterReflection\Reflector\Exception\IdentifierNotFound;
use PhpParser\Node;
use PhpParser\Parser;
use PhpParser\Lexer;

class Locator
{
    /**
     * @var FindReflectionsInTree
     */
    private $findReflectionsInTree;

    /**
     * @var Parser
     */
    private $parser;

    public function __construct(Reflector $reflector)
    {
        $this->findReflectionsInTree = new FindReflectionsInTree(
            new NodeToReflection($reflector)
        );

        $this->parser = new Parser\Multiple([
            new Parser\Php7(new Lexer()),
            new Parser\Php5(new Lexer())
        ]);
    }

    /**
     * Determine if the AST from a located source contains the identifier
     *
     * @todo improve this implementation to peek instead of just throwing exception
     *
     * @param LocatedSource $locatedSource
     * @param Identifier $identifier
     * @return bool
     */
    public function hasIdentifier(LocatedSource $locatedSource, Identifier $identifier)
    {
        try {
            $this->findReflection($locatedSource, $identifier);
            return true;
        } catch (IdentifierNotFound $identifierNotFoundException) {
            return false;
        }
    }

    /**
     * @param Identifier $identifier
     * @param LocatedSource $locatedSource
     * @return Reflection
     */
    public function findReflection(LocatedSource $locatedSource, Identifier $identifier)
    {
        return $this->findInArray(
            $this->findReflectionsOfType(
                $locatedSource,
                $identifier->getType()
            ),
            $identifier
        );
    }

    /**
     * Get an array of reflections found in a LocatedSource.
     *
     * @param LocatedSource $locatedSource
     * @param IdentifierType $identifierType
     * @return Reflection[]
     */
    public function findReflectionsOfType(LocatedSource $locatedSource, IdentifierType $identifierType)
    {
        return $this->findReflectionsInTree->__invoke(
            $this->parser->parse($locatedSource->getSource()),
            $identifierType,
            $locatedSource
        );
    }

    /**
     * Given an array of Reflections, try to find the identifier.
     *
     * @param Reflection[] $reflections
     * @param Identifier $identifier
     * @return Reflection
     */
    private function findInArray($reflections, Identifier $identifier)
    {
        foreach ($reflections as $reflection) {
            if ($reflection->getName() === $identifier->getName()) {
                return $reflection;
            }
        }

        throw IdentifierNotFound::fromIdentifier($identifier);
    }
}
