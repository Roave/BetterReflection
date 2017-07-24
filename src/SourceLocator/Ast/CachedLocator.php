<?php

namespace Roave\BetterReflection\SourceLocator\Ast;

use Roave\BetterReflection\Identifier\IdentifierType;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\Located\LocatedSource;

/**
 * @internal
 */
class CachedLocator extends Locator
{
    /**
     * @var array|string[]
     */
    private  $cache = [];

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

            if (!array_key_exists($id, $this->cache)) {
                $this->cache[$id] = $this->parser->parse($locatedSource->getSource());
            }

            return $this->findReflectionsInTree->__invoke(
                $reflector,
                $this->cache[$id],
                $identifierType,
                $locatedSource
            );
        } catch (\Exception $exception) {
            throw Exception\ParseToAstFailure::fromLocatedSource($locatedSource, $exception);
        } catch (\Throwable $exception) {
            throw Exception\ParseToAstFailure::fromLocatedSource($locatedSource, $exception);
        }
    }
}
