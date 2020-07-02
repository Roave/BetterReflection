<?php

declare(strict_types=1);

namespace Roave\BetterReflection\SourceLocator\Type;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PhpParser\Parser;
use ReflectionClass as CoreReflectionClass;
use ReflectionException;
use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\Identifier\IdentifierType;
use Roave\BetterReflection\Reflection\Reflection;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\Ast\Exception\ParseToAstFailure;
use Roave\BetterReflection\SourceLocator\Ast\Strategy\NodeToReflection;
use Roave\BetterReflection\SourceLocator\Exception\EvaledAnonymousClassCannotBeLocated;
use Roave\BetterReflection\SourceLocator\Exception\TwoAnonymousClassesOnSameLine;
use Roave\BetterReflection\SourceLocator\FileChecker;
use Roave\BetterReflection\SourceLocator\Located\LocatedSource;
use Roave\BetterReflection\Util\FileHelper;

use function array_filter;
use function array_values;
use function assert;
use function file_get_contents;
use function strpos;

/**
 * @internal
 */
final class AnonymousClassObjectSourceLocator implements SourceLocator
{
    private CoreReflectionClass $coreClassReflection;

    private Parser $parser;

    /**
     * @throws ReflectionException
     */
    public function __construct(object $anonymousClassObject, Parser $parser)
    {
        $this->coreClassReflection = new CoreReflectionClass($anonymousClassObject);
        $this->parser              = $parser;
    }

    /**
     * {@inheritDoc}
     *
     * @throws ParseToAstFailure
     */
    public function locateIdentifier(Reflector $reflector, Identifier $identifier): ?Reflection
    {
        return $this->getReflectionClass($reflector, $identifier->getType());
    }

    /**
     * {@inheritDoc}
     *
     * @throws ParseToAstFailure
     */
    public function locateIdentifiersByType(Reflector $reflector, IdentifierType $identifierType): array
    {
        return array_filter([$this->getReflectionClass($reflector, $identifierType)]);
    }

    private function getReflectionClass(Reflector $reflector, IdentifierType $identifierType): ?ReflectionClass
    {
        if (! $identifierType->isClass()) {
            return null;
        }

        $fileName = $this->coreClassReflection->getFileName();

        if (strpos($fileName, 'eval()\'d code') !== false) {
            throw EvaledAnonymousClassCannotBeLocated::create();
        }

        FileChecker::assertReadableFile($fileName);

        $fileName = FileHelper::normalizeWindowsPath($fileName);

        $nodeVisitor = new class ($fileName, $this->coreClassReflection->getStartLine()) extends NodeVisitorAbstract
        {
            private string $fileName;

            private int $startLine;

            /** @var Class_[] */
            private array $anonymousClassNodes = [];

            public function __construct(string $fileName, int $startLine)
            {
                $this->fileName  = $fileName;
                $this->startLine = $startLine;
            }

            /**
             * {@inheritDoc}
             */
            public function enterNode(Node $node)
            {
                if (! ($node instanceof Node\Stmt\Class_) || $node->name !== null) {
                    return null;
                }

                $this->anonymousClassNodes[] = $node;

                return null;
            }

            public function getAnonymousClassNode(): ?Class_
            {
                /** @var Class_[] $anonymousClassNodesOnSameLine */
                $anonymousClassNodesOnSameLine = array_values(array_filter($this->anonymousClassNodes, function (Class_ $node): bool {
                    return $node->getLine() === $this->startLine;
                }));

                if (! $anonymousClassNodesOnSameLine) {
                    return null;
                }

                if (isset($anonymousClassNodesOnSameLine[1])) {
                    throw TwoAnonymousClassesOnSameLine::create($this->fileName, $this->startLine);
                }

                return $anonymousClassNodesOnSameLine[0];
            }
        };

        $fileContents = file_get_contents($fileName);
        $ast          = $this->parser->parse($fileContents);

        $nodeTraverser = new NodeTraverser();
        $nodeTraverser->addVisitor($nodeVisitor);
        $nodeTraverser->traverse($ast);

        $reflectionClass = (new NodeToReflection())->__invoke(
            $reflector,
            $nodeVisitor->getAnonymousClassNode(),
            new LocatedSource($fileContents, $fileName),
            null,
        );
        assert($reflectionClass instanceof ReflectionClass || $reflectionClass === null);

        return $reflectionClass;
    }
}
