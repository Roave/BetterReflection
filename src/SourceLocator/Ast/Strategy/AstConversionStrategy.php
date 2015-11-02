<?php

namespace BetterReflection\SourceLocator\Ast\Strategy;

use BetterReflection\Reflector\Reflector;
use BetterReflection\SourceLocator\Located\LocatedSource;
use PhpParser\Node;

/**
 * @internal
 */
interface AstConversionStrategy
{
    /**
     * Take an AST node in some located source (potentially in a namespace) and
     * convert it to something (concrete implementation decides)
     *
     * @param Reflector $reflector
     * @param Node $node
     * @param LocatedSource $locatedSource
     * @param Node\Stmt\Namespace_|null $namespace
     * @return mixed
     */
    public function __invoke(Reflector $reflector, Node $node, LocatedSource $locatedSource, Node\Stmt\Namespace_ $namespace = null);
}
