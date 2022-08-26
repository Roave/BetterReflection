<?php

declare(strict_types=1);

namespace Roave\BetterReflection\SourceLocator\Ast;

use PhpParser\Node;
use PhpParser\Parser;
use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\Identifier\IdentifierType;
use Roave\BetterReflection\Reflection\Reflection;
use Roave\BetterReflection\Reflector\Exception\IdentifierNotFound;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\Ast\Strategy\NodeToReflection;
use Roave\BetterReflection\SourceLocator\Located\LocatedSource;
use Throwable;

use function strtolower;

/** @internal */
class Locator
{
    private FindReflectionsInTree $findReflectionsInTree;

    public function __construct(private Parser $parser)
    {
        $this->findReflectionsInTree = new FindReflectionsInTree(new NodeToReflection());
    }

    /**
     * @throws IdentifierNotFound
     * @throws Exception\ParseToAstFailure
     */
    public function findReflection(
        Reflector $reflector,
        LocatedSource $locatedSource,
        Identifier $identifier,
    ): Reflection {
        return $this->findInArray(
            $this->findReflectionsOfType(
                $reflector,
                $locatedSource,
                $identifier->getType(),
            ),
            $identifier,
            $locatedSource->getName(),
        );
    }

    /**
     * Get an array of reflections found in some code.
     *
     * @return list<Reflection>
     *
     * @throws Exception\ParseToAstFailure
     */
    public function findReflectionsOfType(
        Reflector $reflector,
        LocatedSource $locatedSource,
        IdentifierType $identifierType,
    ): array {
        try {
            /** @var list<Node\Stmt> $ast */
            $ast = $this->parser->parse($locatedSource->getSource());

            return $this->findReflectionsInTree->__invoke(
                $reflector,
                $ast,
                $identifierType,
                $locatedSource,
            );
        } catch (Throwable $exception) {
            throw Exception\ParseToAstFailure::fromLocatedSource($locatedSource, $exception);
        }
    }

    /**
     * Given an array of Reflections, try to find the identifier.
     *
     * @param list<Reflection> $reflections
     *
     * @throws IdentifierNotFound
     */
    private function findInArray(array $reflections, Identifier $identifier, string|null $name): Reflection
    {
        if ($name === null) {
            throw IdentifierNotFound::fromIdentifier($identifier);
        }

        $identifierName = strtolower($name);

        foreach ($reflections as $reflection) {
            if (strtolower($reflection->getName()) === $identifierName) {
                return $reflection;
            }
        }

        throw IdentifierNotFound::fromIdentifier($identifier);
    }
}
