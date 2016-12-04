<?php

namespace BetterReflection\SourceLocator\Ast;

use BetterReflection\Identifier\Identifier;
use BetterReflection\Identifier\IdentifierType;
use BetterReflection\SourceLocator\Ast\Strategy\NodeToReflection;
use BetterReflection\Reflection\Reflection;
use BetterReflection\Reflector\Reflector;
use BetterReflection\Reflector\Exception\IdentifierNotFound;
use BetterReflection\SourceLocator\Located\LocatedSource;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PhpParser\Lexer;

/**
 * @internal
 */
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

    public function __construct()
    {
        $this->findReflectionsInTree = new FindReflectionsInTree(new NodeToReflection());

        $lexer = new Lexer([ 'usedAttributes' => [ 'comments', 'startFilePos', 'endFilePos', 'startLine', 'endLine' ] ]);
        $this->parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7, $lexer);
    }

    /**
     * @param Reflector $reflector
     * @param LocatedSource $locatedSource
     * @param Identifier $identifier
     * @return Reflection
     * @throws Exception\ParseToAstFailure
     */
    public function findReflection(Reflector $reflector, LocatedSource $locatedSource, Identifier $identifier)
    {
        return $this->findInArray(
            $this->findReflectionsOfType(
                $reflector,
                $locatedSource,
                $identifier->getType()
            ),
            $identifier
        );
    }

    /**
     * Get an array of reflections found in some code.
     *
     * @param Reflector $reflector
     * @param LocatedSource $locatedSource
     * @param IdentifierType $identifierType
     * @return \BetterReflection\Reflection\Reflection[]
     * @throws Exception\ParseToAstFailure
     */
    public function findReflectionsOfType(Reflector $reflector, LocatedSource $locatedSource, IdentifierType $identifierType)
    {
        try {
            return $this->findReflectionsInTree->__invoke(
                $reflector,
                $this->parser->parse($locatedSource->getSource()),
                $identifierType,
                $locatedSource
            );
        } catch (\Exception $exception) {
            throw Exception\ParseToAstFailure::fromLocatedSource($locatedSource, $exception);
        } catch (\Throwable $exception) {
            throw Exception\ParseToAstFailure::fromLocatedSource($locatedSource, $exception);
        }
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
