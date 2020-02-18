<?php

declare(strict_types=1);

namespace Roave\BetterReflection\SourceLocator\Ast;

use Closure;
use PhpParser\Parser;
use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\Identifier\IdentifierType;
use Roave\BetterReflection\Reflection\Reflection;
use Roave\BetterReflection\Reflector\Exception\IdentifierNotFound;
use Roave\BetterReflection\Reflector\FunctionReflector;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\Ast\Strategy\NodeToReflection;
use Roave\BetterReflection\SourceLocator\Located\LocatedSource;
use Throwable;
use function strtolower;

/**
 * @internal
 */
class Locator
{
    /** @var FindReflectionsInTree */
    private $findReflectionsInTree;

    /** @var Parser */
    private $parser;

    /**
     * @param Closure(): FunctionReflector $functionReflectorGetter
     */
    public function __construct(Parser $parser, Closure $functionReflectorGetter)
    {
        $this->findReflectionsInTree = new FindReflectionsInTree(new NodeToReflection(), $functionReflectorGetter);

        $this->parser = $parser;
    }

    /**
     * @throws IdentifierNotFound
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
     * @return Reflection[]
     *
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
     *
     * @throws IdentifierNotFound
     */
    private function findInArray(array $reflections, Identifier $identifier) : Reflection
    {
        $identifierName = strtolower($identifier->getName());

        foreach ($reflections as $reflection) {
            if (strtolower($reflection->getName()) === $identifierName) {
                return $reflection;
            }
        }

        throw IdentifierNotFound::fromIdentifier($identifier);
    }
}
