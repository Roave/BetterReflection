<?php
declare(strict_types=1);

namespace Rector\BetterReflection\SourceLocator\Type;

use Closure;
use PhpParser\Node;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PhpParser\Parser;
use ReflectionFunction as CoreFunctionReflection;
use Rector\BetterReflection\Identifier\Identifier;
use Rector\BetterReflection\Identifier\IdentifierType;
use Rector\BetterReflection\Reflection\Reflection;
use Rector\BetterReflection\Reflection\ReflectionFunction;
use Rector\BetterReflection\Reflector\Reflector;
use Rector\BetterReflection\SourceLocator\Ast\Strategy\NodeToReflection;
use Rector\BetterReflection\SourceLocator\Exception\EvaledClosureCannotBeLocated;
use Rector\BetterReflection\SourceLocator\Exception\TwoClosuresOnSameLine;
use Rector\BetterReflection\SourceLocator\FileChecker;
use Rector\BetterReflection\SourceLocator\Located\LocatedSource;
use Rector\BetterReflection\Util\FileHelper;

/**
 * @internal
 */
final class ClosureSourceLocator implements SourceLocator
{
    /**
     * @var CoreFunctionReflection
     */
    private $coreFunctionReflection;

    /**
     * @var Parser
     */
    private $parser;

    public function __construct(Closure $closure, Parser $parser)
    {
        $this->coreFunctionReflection = new CoreFunctionReflection($closure);
        $this->parser                 = $parser;
    }

    /**
     * {@inheritDoc}
     *
     * @throws \Rector\BetterReflection\SourceLocator\Ast\Exception\ParseToAstFailure
     */
    public function locateIdentifier(Reflector $reflector, Identifier $identifier) : ?Reflection
    {
        return $this->getReflectionFunction($reflector, $identifier->getType());
    }

    /**
     * {@inheritDoc}
     *
     * @throws \Rector\BetterReflection\SourceLocator\Ast\Exception\ParseToAstFailure
     */
    public function locateIdentifiersByType(Reflector $reflector, IdentifierType $identifierType) : array
    {
        return \array_filter([$this->getReflectionFunction($reflector, $identifierType)]);
    }

    private function getReflectionFunction(Reflector $reflector, IdentifierType $identifierType) : ?ReflectionFunction
    {
        if ( ! $identifierType->isFunction()) {
            return null;
        }

        $fileName = $this->coreFunctionReflection->getFileName();

        if (false !== \strpos($fileName, 'eval()\'d code')) {
            throw EvaledClosureCannotBeLocated::create();
        }

        FileChecker::assertReadableFile($fileName);

        $fileName = FileHelper::normalizeWindowsPath($fileName);

        $nodeVisitor = new class($fileName, $this->coreFunctionReflection->getStartLine()) extends NodeVisitorAbstract
        {
            /**
             * @var string
             */
            private $fileName;

            /**
             * @var int
             */
            private $startLine;

            /**
             * @var Node[][]
             */
            private $closureNodes = [];

            /**
             * @var Namespace_|null
             */
            private $currentNamespace;

            public function __construct(string $fileName, int $startLine)
            {
                $this->fileName  = $fileName;
                $this->startLine = $startLine;
            }

            public function enterNode(Node $node) : void
            {
                if ($node instanceof Namespace_) {
                    $this->currentNamespace = $node;

                    return;
                }

                if ($node instanceof Node\Expr\Closure) {
                    $this->closureNodes[] = [$node, $this->currentNamespace];
                }
            }

            public function leaveNode(Node $node) : void
            {
                if ($node instanceof Namespace_) {
                    $this->currentNamespace = null;
                }
            }

            /**
             * @return Node[]|null[]|null
             *
             * @throws TwoClosuresOnSameLine
             */
            public function getClosureNodes() : ?array
            {
                /** @var Node[][] $closureNodesDataOnSameLine */
                $closureNodesDataOnSameLine = \array_values(\array_filter($this->closureNodes, function (array $nodes) : bool {
                    return $nodes[0]->getLine() === $this->startLine;
                }));

                if ( ! $closureNodesDataOnSameLine) {
                    return null;
                }

                if (isset($closureNodesDataOnSameLine[1])) {
                    throw TwoClosuresOnSameLine::create($this->fileName, $this->startLine);
                }

                return $closureNodesDataOnSameLine[0];
            }
        };

        $fileContents = \file_get_contents($fileName);
        $ast          = $this->parser->parse($fileContents);

        $nodeTraverser = new NodeTraverser();
        $nodeTraverser->addVisitor($nodeVisitor);
        $nodeTraverser->traverse($ast);

        $closureNodes = $nodeVisitor->getClosureNodes();

        /** @var ReflectionFunction|null $reflectionFunction */
        $reflectionFunction = (new NodeToReflection())->__invoke(
            $reflector,
            $closureNodes[0],
            new LocatedSource($fileContents, $fileName),
            $closureNodes[1]
        );

        return $reflectionFunction;
    }
}
