<?php

namespace BetterReflection\SourceLocator\Type;

use Closure;
use BetterReflection\Identifier\Identifier;
use BetterReflection\Identifier\IdentifierType;
use BetterReflection\Reflector\Reflector;
use BetterReflection\SourceLocator\Ast\Strategy\AstConversionStrategy;
use BetterReflection\SourceLocator\Ast\Strategy\NodeToReflection;
use BetterReflection\SourceLocator\Located\LocatedSource;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Namespace_;
use SuperClosure\Analyzer\AstAnalyzer;
use SuperClosure\Analyzer\ClosureAnalyzer;
use PhpParser\Node\Expr\Closure as ClosureNode;

class ClosureSourceLocator implements SourceLocator
{
    /**
     * @var Closure
     */
    private $closure;

    /**
     * @var ClosureAnalyzer
     */
    private $closureAnalyzer;

    /**
     * @var AstConversionStrategy
     */
    private $conversionStrategy;

    public function __construct(Closure $closure, ClosureAnalyzer $closureAnalyzer = null, AstConversionStrategy $conversionStrategy = null)
    {
        $this->closure = $closure;

        if (null === $closureAnalyzer) {
            $closureAnalyzer = new AstAnalyzer();
        }
        $this->closureAnalyzer = $closureAnalyzer;

        if (null === $conversionStrategy) {
            $conversionStrategy = new NodeToReflection();
        }
        $this->conversionStrategy = $conversionStrategy;
    }

    /**
     * {@inheritDoc}
     */
    public function locateIdentifier(Reflector $reflector, Identifier $identifier)
    {
        $closureData = $this->closureAnalyzer->analyze($this->closure);

        if (!isset($closureData['ast']) || !($closureData['ast'] instanceof ClosureNode)) {
            return null;
        }

        $locatedSource = new LocatedSource(
            '<?php ' . $closureData['code'],
            $closureData['reflection']->getFileName()
        );

        $namespaceNode = null;
        if (isset($closureData['location']['namespace'])) {
            $namespaceNode = new Namespace_(new Name($closureData['location']['namespace']));
        }

        return $this->conversionStrategy->__invoke(
            $reflector,
            $closureData['ast'],
            $locatedSource,
            $namespaceNode
        );
    }

    public function locateIdentifiersByType(Reflector $reflector, IdentifierType $identifierType)
    {
        // @todo https://github.com/Roave/BetterReflection/issues/152
        throw new \LogicException('Not implemented - Unable to reflect closures in this way currently');
    }
}
