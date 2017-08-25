<?php
declare(strict_types=1);

namespace Roave\BetterReflection\SourceLocator\Ast;

use PhpParser\Parser;
use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\Identifier\IdentifierType;
use Roave\BetterReflection\Reflection\Reflection;
use Roave\BetterReflection\Reflector\Exception\IdentifierNotFound;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\Ast\Strategy\NodeToReflection;
use Roave\BetterReflection\SourceLocator\Located\LocatedSource;
use Throwable;

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

        $this->parser = PhpParserFactory::create();
    }

    /**
     * @param Reflector $reflector
     * @param LocatedSource $locatedSource
     * @param Identifier $identifier
     * @return Reflection
     * @throws \Roave\BetterReflection\Reflector\Exception\IdentifierNotFound
     * @throws Exception\ParseToAstFailure
     */
    public function findReflection(
        Reflector $reflector,
        LocatedSource $locatedSource,
        Identifier $identifier
    ) : Reflection {
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
     * @return \Roave\BetterReflection\Reflection\Reflection[]
     * @throws Exception\ParseToAstFailure
     */
    public function findReflectionsOfType(
        Reflector $reflector,
        LocatedSource $locatedSource,
        IdentifierType $identifierType
    ) : array {
        try {
            return $this->findReflectionsInTree->__invoke(
                $reflector,
                $this->parser->parse($locatedSource->getSource()),
                $identifierType,
                $locatedSource
            );
        } catch (Throwable $exception) {
            throw Exception\ParseToAstFailure::fromLocatedSource($locatedSource, $exception);
        }
    }

    /**
     * Given an array of Reflections, try to find the identifier.
     *
     * @param Reflection[] $reflections
     * @param Identifier $identifier
     * @return Reflection
     * @throws \Roave\BetterReflection\Reflector\Exception\IdentifierNotFound
     */
    private function findInArray(array $reflections, Identifier $identifier) : Reflection
    {
        foreach ($reflections as $reflection) {
            if ($reflection->getName() === $identifier->getName()) {
                return $reflection;
            }
        }

        throw IdentifierNotFound::fromIdentifier($identifier);
    }
}
