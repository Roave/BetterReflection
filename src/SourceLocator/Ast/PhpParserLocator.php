<?php
declare(strict_types=1);

namespace Roave\BetterReflection\SourceLocator\Ast;

use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\Identifier\IdentifierType;
use Roave\BetterReflection\SourceLocator\Ast\Parser\MemoizingParser;
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
final class PhpParserLocator implements Locator
{
    /**
     * @var FindReflectionsInTree
     */
    private $findReflectionsInTree;

    /**
     * @var Parser
     */
    private $parser;

    public function __construct(?Parser $parser = null)
    {
        $this->findReflectionsInTree = new FindReflectionsInTree(new NodeToReflection());

        $this->parser = $parser ?: new MemoizingParser((new ParserFactory())->create(ParserFactory::PREFER_PHP7));
    }

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
     *
     * @return Reflection
     *
     * @throws IdentifierNotFound
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
