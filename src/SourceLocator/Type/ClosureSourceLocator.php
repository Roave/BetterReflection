<?php

namespace Roave\BetterReflection\SourceLocator\Type;

use Closure;
use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\Identifier\IdentifierType;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\Ast\Strategy\AstConversionStrategy;
use Roave\BetterReflection\SourceLocator\Ast\Strategy\NodeToReflection;
use Roave\BetterReflection\SourceLocator\Located\LocatedSource;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Namespace_;
use SuperClosure\Analyzer\AstAnalyzer;
use SuperClosure\Analyzer\ClosureAnalyzer;
use PhpParser\Node\Expr\Closure as ClosureNode;
use SuperClosure\Exception\ClosureAnalysisException;
use Roave\BetterReflection\SourceLocator\Exception\TwoClosuresOneLine;

final class ClosureSourceLocator implements SourceLocator
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
        try {
            $closureData = $this->closureAnalyzer->analyze($this->closure);
        } catch (ClosureAnalysisException $closureAnalysisException) {
            if (stripos($closureAnalysisException->getMessage(), 'Two closures were declared on the same line') !== false) {
                throw TwoClosuresOneLine::fromClosureAnalysisException($closureAnalysisException);
            }

            throw $closureAnalysisException;
        }

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
