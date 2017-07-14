<?php

namespace Roave\BetterReflection\SourceLocator\Ast;

use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\Identifier\IdentifierType;
use Roave\BetterReflection\SourceLocator\Ast\Strategy\NodeToReflection;
use Roave\BetterReflection\Reflection\Reflection;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\Reflector\Exception\IdentifierNotFound;
use Roave\BetterReflection\SourceLocator\Located\LocatedSource;
use PhpParser\Parser;
use PhpParser\ParserFactory;

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

    /**
     * @var array|string[]
     */
    private static $cache = [];

    public function __construct()
    {
        $this->findReflectionsInTree = new FindReflectionsInTree(new NodeToReflection());

        $this->parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
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
            $id = $locatedSource->getFileName();
            if ($id === null) {
                // if source is native stub
                $id = sha1($locatedSource->getSource());
            }

            if (!array_key_exists($id, self::$cache)) {
                self::$cache[$id] = $this->parser->parse($locatedSource->getSource());
            }

            return $this->findReflectionsInTree->__invoke(
                $reflector,
                self::$cache[$id],
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
