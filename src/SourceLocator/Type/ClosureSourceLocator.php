<?php

declare(strict_types=1);

namespace Roave\BetterReflection\SourceLocator\Type;

use Closure;
use PhpParser\Node;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\NodeVisitorAbstract;
use PhpParser\Parser;
use ReflectionFunction as CoreFunctionReflection;
use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\Identifier\IdentifierType;
use Roave\BetterReflection\Reflection\Reflection;
use Roave\BetterReflection\Reflection\ReflectionFunction;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\Ast\Exception\ParseToAstFailure;
use Roave\BetterReflection\SourceLocator\Ast\Strategy\NodeToReflection;
use Roave\BetterReflection\SourceLocator\Exception\EvaledClosureCannotBeLocated;
use Roave\BetterReflection\SourceLocator\Exception\NoClosureOnLine;
use Roave\BetterReflection\SourceLocator\Exception\TwoClosuresOnSameLine;
use Roave\BetterReflection\SourceLocator\FileChecker;
use Roave\BetterReflection\SourceLocator\Located\AnonymousLocatedSource;
use Roave\BetterReflection\Util\FileHelper;

use function array_filter;
use function assert;
use function file_get_contents;
use function strpos;

/** @internal */
final class ClosureSourceLocator implements SourceLocator
{
    private CoreFunctionReflection $coreFunctionReflection;

    public function __construct(Closure $closure, private Parser $parser)
    {
        $this->coreFunctionReflection = new CoreFunctionReflection($closure);
    }

    /**
     * {@inheritDoc}
     *
     * @throws ParseToAstFailure
     */
    public function locateIdentifier(Reflector $reflector, Identifier $identifier): Reflection|null
    {
        return $this->getReflectionFunction($reflector, $identifier->getType());
    }

    /**
     * {@inheritDoc}
     *
     * @throws ParseToAstFailure
     */
    public function locateIdentifiersByType(Reflector $reflector, IdentifierType $identifierType): array
    {
        return array_filter([$this->getReflectionFunction($reflector, $identifierType)]);
    }

    private function getReflectionFunction(Reflector $reflector, IdentifierType $identifierType): ReflectionFunction|null
    {
        if (! $identifierType->isFunction()) {
            return null;
        }

        $fileName = $this->coreFunctionReflection->getFileName();

        if (strpos($fileName, 'eval()\'d code') !== false) {
            throw EvaledClosureCannotBeLocated::create();
        }

        FileChecker::assertReadableFile($fileName);

        $fileName = FileHelper::normalizeWindowsPath($fileName);

        $nodeVisitor = new class ($fileName, $this->coreFunctionReflection->getStartLine()) extends NodeVisitorAbstract
        {
            /** @var list<array{node: Node\Expr\Closure|Node\Expr\ArrowFunction, namespace: Namespace_|null}> */
            private array $closureNodes = [];

            private Namespace_|null $currentNamespace = null;

            public function __construct(private string $fileName, private int $startLine)
            {
            }

            /**
             * {@inheritDoc}
             */
            public function enterNode(Node $node)
            {
                if ($node instanceof Namespace_) {
                    $this->currentNamespace = $node;

                    return null;
                }

                if (
                    $node->getStartLine() === $this->startLine
                    && ($node instanceof Node\Expr\Closure || $node instanceof Node\Expr\ArrowFunction)
                ) {
                    $this->closureNodes[] = ['node' => $node, 'namespace' => $this->currentNamespace];
                }

                return null;
            }

            /**
             * {@inheritDoc}
             */
            public function leaveNode(Node $node)
            {
                if (! ($node instanceof Namespace_)) {
                    return null;
                }

                $this->currentNamespace = null;

                return null;
            }

            /**
             * @return array{node: Node\Expr\Closure|Node\Expr\ArrowFunction, namespace: Namespace_|null}
             *
             * @throws NoClosureOnLine
             * @throws TwoClosuresOnSameLine
             */
            public function getClosureNodes(): array
            {
                if ($this->closureNodes === []) {
                    throw NoClosureOnLine::create($this->fileName, $this->startLine);
                }

                if (isset($this->closureNodes[1])) {
                    throw TwoClosuresOnSameLine::create($this->fileName, $this->startLine);
                }

                return $this->closureNodes[0];
            }
        };

        $fileContents = file_get_contents($fileName);
        /** @var list<Node\Stmt> $ast */
        $ast = $this->parser->parse($fileContents);

        $nodeTraverser = new NodeTraverser();
        $nodeTraverser->addVisitor(new NameResolver());
        $nodeTraverser->addVisitor($nodeVisitor);
        $nodeTraverser->traverse($ast);

        $closureNodes = $nodeVisitor->getClosureNodes();

        $reflectionFunction = (new NodeToReflection())->__invoke(
            $reflector,
            $closureNodes['node'],
            new AnonymousLocatedSource($fileContents, $fileName),
            $closureNodes['namespace'],
        );
        assert($reflectionFunction instanceof ReflectionFunction);

        return $reflectionFunction;
    }
}
