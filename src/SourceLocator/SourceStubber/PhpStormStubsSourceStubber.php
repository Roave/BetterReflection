<?php

declare(strict_types=1);

namespace Roave\BetterReflection\SourceLocator\SourceStubber;

use DirectoryIterator;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\NodeVisitorAbstract;
use PhpParser\Parser;
use PhpParser\PrettyPrinter\Standard;
use ReflectionClass as CoreReflectionClass;
use ReflectionFunction as CoreReflectionFunction;
use Roave\BetterReflection\SourceLocator\FileChecker;
use Roave\BetterReflection\SourceLocator\SourceStubber\Exception\CouldNotFindPhpStormStubs;
use Traversable;
use function array_key_exists;
use function file_get_contents;
use function is_dir;
use function sprintf;
use function str_replace;

/**
 * @internal
 */
final class PhpStormStubsSourceStubber implements SourceStubber
{
    private const BUILDER_OPTIONS    = ['shortArraySyntax' => true];
    private const SEARCH_DIRECTORIES = [
        __DIR__ . '/../../../../../jetbrains/phpstorm-stubs',
        __DIR__ . '/../../../vendor/jetbrains/phpstorm-stubs',
    ];

    /** @var Parser */
    private $phpParser;

    /** @var Standard */
    private $prettyPrinter;

    /** @var NodeTraverser */
    private $nodeTraverser;

    /** @var string|null */
    private $stubsDirectory;

    /** @var string[][] */
    private $extensionStubsFiles = [];

    public function __construct(Parser $phpParser)
    {
        $this->phpParser     = $phpParser;
        $this->prettyPrinter = new Standard(self::BUILDER_OPTIONS);

        $this->nodeTraverser = new NodeTraverser();
        $this->nodeTraverser->addVisitor(new NameResolver());
    }

    /**
     * {@inheritDoc}
     */
    public function generateClassStub(CoreReflectionClass $classReflection) : ?string
    {
        if ($classReflection->isUserDefined()) {
            return null;
        }

        $stub = $this->getStub($classReflection->getExtensionName(), $this->getClassNodeVisitor($classReflection));

        if ($classReflection->getName() === Traversable::class) {
            // See https://github.com/JetBrains/phpstorm-stubs/commit/0778a26992c47d7dbee4d0b0bfb7fad4344371b1#diff-575bacb45377d474336c71cbf53c1729
            $stub = str_replace(' extends \iterable', '', $stub);
        }

        return $stub;
    }

    /**
     * {@inheritDoc}
     */
    public function generateFunctionStub(CoreReflectionFunction $functionReflection) : ?string
    {
        if ($functionReflection->isUserDefined()) {
            return null;
        }

        return $this->getStub($functionReflection->getExtensionName(), $this->getFunctionNodeVisitor($functionReflection));
    }

    private function getStub(string $extensionName, NodeVisitorAbstract $nodeVisitor) : ?string
    {
        $node = null;

        $this->nodeTraverser->addVisitor($nodeVisitor);

        foreach ($this->getExtensionStubsFiles($extensionName) as $filePath) {
            FileChecker::assertReadableFile($filePath);

            $ast = $this->phpParser->parse(file_get_contents($filePath));

            $this->nodeTraverser->traverse($ast);

            /** @psalm-suppress UndefinedMethod */
            $node = $nodeVisitor->getNode();
            if ($node !== null) {
                break;
            }
        }

        $this->nodeTraverser->removeVisitor($nodeVisitor);

        if ($node === null) {
            return null;
        }

        return "<?php\n\n" . $this->prettyPrinter->prettyPrint([$node]) . "\n";
    }

    private function getClassNodeVisitor(CoreReflectionClass $classReflection) : NodeVisitorAbstract
    {
        return new class($classReflection->getName()) extends NodeVisitorAbstract
        {
            /** @var string */
            private $className;

            /** @var Node\Stmt\ClassLike|null */
            private $node;

            public function __construct(string $className)
            {
                $this->className = $className;
            }

            public function enterNode(Node $node) : ?int
            {
                if ($node instanceof Node\Stmt\Namespace_) {
                    return null;
                }

                if ($node instanceof Node\Stmt\ClassLike && $node->namespacedName->toString() === $this->className) {
                    $this->node = $node;
                    return NodeTraverser::STOP_TRAVERSAL;
                }

                return NodeTraverser::DONT_TRAVERSE_CHILDREN;
            }

            public function getNode() : ?Node\Stmt\ClassLike
            {
                return $this->node;
            }
        };
    }

    private function getFunctionNodeVisitor(CoreReflectionFunction $functionReflection) : NodeVisitorAbstract
    {
        return new class($functionReflection->getName()) extends NodeVisitorAbstract
        {
            /** @var string */
            private $functionName;

            /** @var Node\Stmt\Function_|null */
            private $node;

            public function __construct(string $className)
            {
                $this->functionName = $className;
            }

            public function enterNode(Node $node) : ?int
            {
                if ($node instanceof Node\Stmt\Namespace_) {
                    return null;
                }

                /** @psalm-suppress UndefinedPropertyFetch */
                if ($node instanceof Node\Stmt\Function_ && $node->namespacedName->toString() === $this->functionName) {
                    $this->node = $node;
                    return NodeTraverser::STOP_TRAVERSAL;
                }

                return NodeTraverser::DONT_TRAVERSE_CHILDREN;
            }

            public function getNode() : ?Node\Stmt\Function_
            {
                return $this->node;
            }
        };
    }

    /**
     * @return string[]
     */
    private function getExtensionStubsFiles(string $extensionName) : array
    {
        if (array_key_exists($extensionName, $this->extensionStubsFiles)) {
            return $this->extensionStubsFiles[$extensionName];
        }

        $this->extensionStubsFiles[$extensionName] = [];

        $extensionDirectory = sprintf('%s/%s', $this->getStubsDirectory(), $extensionName);

        if (! is_dir($extensionDirectory)) {
            return [];
        }

        foreach (new DirectoryIterator($extensionDirectory) as $fileInfo) {
            if ($fileInfo->isDot()) {
                continue;
            }

            $this->extensionStubsFiles[$extensionName][] = $fileInfo->getPathname();
        }

        return $this->extensionStubsFiles[$extensionName];
    }

    private function getStubsDirectory() : string
    {
        if ($this->stubsDirectory !== null) {
            return $this->stubsDirectory;
        }

        foreach (self::SEARCH_DIRECTORIES as $directory) {
            if (is_dir($directory)) {
                return $this->stubsDirectory = $directory;
            }
        }

        throw CouldNotFindPhpStormStubs::create();
    }
}
