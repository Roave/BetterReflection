<?php
declare(strict_types=1);

namespace Roave\BetterReflection\SourceLocator\Type;

use phpDocumentor\Reflection\File;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;
use ReflectionClass as CoreReflectionClass;
use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\Identifier\IdentifierType;
use Roave\BetterReflection\Reflection\Reflection;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\Ast\Strategy\NodeToReflection;
use Roave\BetterReflection\SourceLocator\Exception\EvaledAnonymousClassCannotBeLocated;
use Roave\BetterReflection\SourceLocator\Exception\TwoAnonymousClassesOnSameLine;
use Roave\BetterReflection\SourceLocator\FileChecker;
use Roave\BetterReflection\SourceLocator\Located\LocatedSource;
use Roave\BetterReflection\Util\FileHelper;

final class AnonymousClassObjectSourceLocator implements SourceLocator
{
    /**
     * @var CoreReflectionClass
     */
    private $coreClassReflection;

    /**
     * @param object $anonymousClassObject
     */
    public function __construct($anonymousClassObject)
    {
        if (gettype($anonymousClassObject) !== 'object') {
            throw new \InvalidArgumentException('Can only create from an instance of an object');
        }
        $this->coreClassReflection = new CoreReflectionClass($anonymousClassObject);
    }

    /**
     * {@inheritDoc}
     * @throws \Roave\BetterReflection\SourceLocator\Ast\Exception\ParseToAstFailure
     */
    public function locateIdentifier(Reflector $reflector, Identifier $identifier) : ?Reflection
    {
        return $this->getReflectionClass($reflector, $identifier->getType());
    }

    /**
     * {@inheritDoc}
     * @throws \Roave\BetterReflection\SourceLocator\Ast\Exception\ParseToAstFailure
     */
    public function locateIdentifiersByType(Reflector $reflector, IdentifierType $identifierType) : array
    {
        return array_filter([$this->getReflectionClass($reflector, $identifierType)]);
    }

    private function getReflectionClass(Reflector $reflector, IdentifierType $identifierType) : ?ReflectionClass
    {
        if (!$identifierType->isClass()) {
            return null;
        }

        $fileName = $this->coreClassReflection->getFileName();

        if (strpos($fileName, 'eval()\'d code') !== false) {
            throw EvaledAnonymousClassCannotBeLocated::create();
        }

        FileChecker::checkFile($fileName);

        $fileName = FileHelper::normalizeWindowsPath($fileName);

        $nodeVisitor = new class($fileName, $this->coreClassReflection->getStartLine()) extends NodeVisitorAbstract
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
             * @var Class_[]
             */
            private $anonymousClassNodes = [];

            public function __construct(string $fileName, int $startLine)
            {
                $this->fileName = $fileName;
                $this->startLine = $startLine;
            }

            public function enterNode(Node $node) : void
            {
                if ($node instanceof Node\Stmt\Class_ && null === $node->name) {
                    $this->anonymousClassNodes[] = $node;
                }
            }

            public function getAnonymousClassNode() : ?Class_
            {
                /** @var Class_[] $anonymousClassNodesOnSameLine */
                $anonymousClassNodesOnSameLine = array_values(array_filter($this->anonymousClassNodes, function (Class_ $node) : bool {
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

        $ast = (new ParserFactory())->create(ParserFactory::PREFER_PHP7)->parse(file_get_contents($fileName));

        $nodeTraverser = new NodeTraverser();
        $nodeTraverser->addVisitor($nodeVisitor);
        $nodeTraverser->traverse($ast);

        return (new NodeToReflection())->__invoke($reflector, $nodeVisitor->getAnonymousClassNode(), new LocatedSource(file_get_contents($fileName), $fileName), null);
    }
}
