<?php
declare(strict_types=1);

namespace Rector\BetterReflection\SourceLocator\Ast\Strategy;

use PhpParser\Node;
use Rector\BetterReflection\Reflection\Reflection;
use Rector\BetterReflection\Reflector\Reflector;
use Rector\BetterReflection\SourceLocator\Located\LocatedSource;

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
     * @return Reflection|null
     */
    public function __invoke(
        Reflector $reflector,
        Node $node,
        LocatedSource $locatedSource,
        ?Node\Stmt\Namespace_ $namespace
    ) : ?Reflection;
}
