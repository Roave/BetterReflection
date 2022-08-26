<?php

declare(strict_types=1);

namespace Roave\BetterReflection\SourceLocator\Type;

use InvalidArgumentException;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\NodeVisitorAbstract;
use PhpParser\Parser;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\Reflection\Exception\InvalidConstantNode;
use Roave\BetterReflection\SourceLocator\Ast\Locator as AstLocator;
use Roave\BetterReflection\SourceLocator\Exception\InvalidFileLocation;
use Roave\BetterReflection\SourceLocator\FileChecker;
use Roave\BetterReflection\SourceLocator\Located\AliasLocatedSource;
use Roave\BetterReflection\SourceLocator\Located\LocatedSource;
use Roave\BetterReflection\SourceLocator\Type\AutoloadSourceLocator\FileReadTrapStreamWrapper;
use Roave\BetterReflection\Util\ClassExistenceChecker;
use Roave\BetterReflection\Util\ConstantNodeChecker;

use function array_key_exists;
use function array_reverse;
use function assert;
use function defined;
use function file_get_contents;
use function function_exists;
use function get_defined_constants;
use function get_included_files;
use function is_file;
use function is_string;
use function restore_error_handler;
use function set_error_handler;
use function spl_autoload_functions;
use function strtolower;

/**
 * Use PHP's built in autoloader to locate a class, without actually loading.
 *
 * There are some prerequisites...
 *   - we expect the autoloader to load classes from a file (i.e. using require/include)
 *   - your autoloader of choice does not replace stream wrappers
 */
class AutoloadSourceLocator extends AbstractSourceLocator
{
    private Parser $phpParser;

    private NodeTraverser $nodeTraverser;

    private NodeVisitorAbstract $constantVisitor;

    public function __construct(AstLocator|null $astLocator = null, Parser|null $phpParser = null)
    {
        $betterReflection = new BetterReflection();

        parent::__construct($astLocator ?? $betterReflection->astLocator());

        $this->phpParser       = $phpParser ?? $betterReflection->phpParser();
        $this->constantVisitor = $this->createConstantVisitor();

        $this->nodeTraverser = new NodeTraverser();
        $this->nodeTraverser->addVisitor(new NameResolver());
        $this->nodeTraverser->addVisitor($this->constantVisitor);
    }

    /**
     * {@inheritDoc}
     *
     * @throws InvalidArgumentException
     * @throws InvalidFileLocation
     */
    protected function createLocatedSource(Identifier $identifier): LocatedSource|null
    {
        $locatedData = $this->attemptAutoloadForIdentifier($identifier);

        if ($locatedData === null) {
            return null;
        }

        if (! is_file($locatedData['fileName'])) {
            return null;
        }

        if (strtolower($identifier->getName()) !== strtolower($locatedData['name'])) {
            return new AliasLocatedSource(
                file_get_contents($locatedData['fileName']),
                $locatedData['name'],
                $locatedData['fileName'],
                $identifier->getName(),
            );
        }

        return new LocatedSource(
            file_get_contents($locatedData['fileName']),
            $identifier->getName(),
            $locatedData['fileName'],
        );
    }

    /**
     * Attempts to locate the specified identifier.
     *
     * @return array{fileName: string, name: string}|null
     *
     * @throws ReflectionException
     */
    private function attemptAutoloadForIdentifier(Identifier $identifier): array|null
    {
        if ($identifier->isClass()) {
            return $this->locateClassByName($identifier->getName());
        }

        if ($identifier->isFunction()) {
            return $this->locateFunctionByName($identifier->getName());
        }

        if ($identifier->isConstant()) {
            return $this->locateConstantByName($identifier->getName());
        }

        return null;
    }

    /**
     * Attempt to locate a class by name.
     *
     * If class already exists, simply use internal reflection API to get the
     * filename and store it.
     *
     * If class does not exist, we make an assumption that whatever autoloaders
     * that are registered will be loading a file. We then override the file://
     * protocol stream wrapper to "capture" the filename we expect the class to
     * be in, and then restore it. Note that class_exists will cause an error
     * that it cannot find the file, so we squelch the errors by overriding the
     * error handler temporarily.
     *
     * Note: the following code is designed so that the first hit on an actual
     *       **file** leads to a path being resolved. No actual autoloading nor
     *       file reading should happen, and most certainly no other classes
     *       should exist after execution. The only filesystem access is to
     *       check whether the file exists.
     *
     * @return array{fileName: string, name: string}|null
     *
     * @throws ReflectionException
     */
    private function locateClassByName(string $className): array|null
    {
        if (ClassExistenceChecker::exists($className)) {
            $classReflection = new ReflectionClass($className);

            $filename = $classReflection->getFileName();

            if (! is_string($filename)) {
                return null;
            }

            return ['fileName' => $filename, 'name' => $classReflection->getName()];
        }

        $this->silenceErrors();

        try {
            $locatedFile = FileReadTrapStreamWrapper::withStreamWrapperOverride(
                static function () use ($className): string|null {
                    foreach (spl_autoload_functions() as $preExistingAutoloader) {
                        $preExistingAutoloader($className);

                        /**
                         * This static variable is populated by the side-effect of the stream wrapper
                         * trying to read the file path when `include()` is used by an autoloader.
                         *
                         * This will not be `null` when the autoloader tried to read a file.
                         */
                        if (FileReadTrapStreamWrapper::$autoloadLocatedFile !== null) {
                            return FileReadTrapStreamWrapper::$autoloadLocatedFile;
                        }
                    }

                    return null;
                },
            );

            if ($locatedFile === null) {
                return null;
            }

            return ['fileName' => $locatedFile, 'name' => $className];
        } finally {
            restore_error_handler();
        }
    }

    private function silenceErrors(): void
    {
        set_error_handler(static fn (): bool => true);
    }

    /**
     * We can only load functions if they already exist, because PHP does not
     * have function autoloading. Therefore if it exists, we simply use the
     * internal reflection API to find the filename. If it doesn't we can do
     * nothing so throw an exception.
     *
     * @return array{fileName: string, name: string}|null
     *
     * @throws ReflectionException
     */
    private function locateFunctionByName(string $functionName): array|null
    {
        if (! function_exists($functionName)) {
            return null;
        }

        $reflectionFileName = (new ReflectionFunction($functionName))->getFileName();

        if (! is_string($reflectionFileName)) {
            return null;
        }

        return ['fileName' => $reflectionFileName, 'name' => $functionName];
    }

    /**
     * We can only load constants if they already exist, because PHP does not
     * have constant autoloading. Therefore if it exists, we simply use brute force
     * to search throughout all included files to find the right filename.
     *
     * @return array{fileName: string, name: string}|null
     */
    private function locateConstantByName(string $constantName): array|null
    {
        if (! defined($constantName)) {
            return null;
        }

        /** @var array<string, array<string, scalar|list<scalar>|resource|null>> $constants */
        $constants = get_defined_constants(true);

        if (! array_key_exists($constantName, $constants['user'])) {
            return null;
        }

        /** @psalm-suppress UndefinedMethod */
        $this->constantVisitor->setConstantName($constantName);

        $constantFileName = null;

        // Note: looking at files in reverse order, since newer files are more likely to have
        //       defined a constant that is being looked up. Earlier files are possibly related
        //       to libraries/frameworks that we rely upon.
        foreach (array_reverse(get_included_files()) as $includedFileName) {
            try {
                FileChecker::assertReadableFile($includedFileName);
            } catch (InvalidFileLocation) {
                continue;
            }

            /** @var list<Node\Stmt> $ast */
            $ast = $this->phpParser->parse(file_get_contents($includedFileName));

            $this->nodeTraverser->traverse($ast);

            /** @psalm-suppress UndefinedMethod */
            if ($this->constantVisitor->getNode() !== null) {
                $constantFileName = $includedFileName;
                break;
            }
        }

        if ($constantFileName === null) {
            return null;
        }

        return ['fileName' => $constantFileName, 'name' => $constantName];
    }

    private function createConstantVisitor(): NodeVisitorAbstract
    {
        return new class () extends NodeVisitorAbstract
        {
            private string|null $constantName = null;

            private Node\Stmt\Const_|Node\Expr\FuncCall|null $node = null;

            public function enterNode(Node $node): int|null
            {
                if ($node instanceof Node\Stmt\Const_) {
                    foreach ($node->consts as $constNode) {
                        if ($constNode->namespacedName?->toString() === $this->constantName) {
                            $this->node = $node;

                            return NodeTraverser::STOP_TRAVERSAL;
                        }
                    }

                    return NodeTraverser::DONT_TRAVERSE_CHILDREN;
                }

                if ($node instanceof Node\Expr\FuncCall) {
                    try {
                        /** @psalm-suppress InternalClass, InternalMethod */
                        ConstantNodeChecker::assertValidDefineFunctionCall($node);
                    } catch (InvalidConstantNode) {
                        return null;
                    }

                    $argumentNameNode = $node->args[0];
                    assert($argumentNameNode instanceof Node\Arg);
                    $nameNode = $argumentNameNode->value;
                    assert($nameNode instanceof Node\Scalar\String_);

                    if ($nameNode->value === $this->constantName) {
                        $this->node = $node;

                        return NodeTraverser::STOP_TRAVERSAL;
                    }
                }

                return null;
            }

            public function setConstantName(string $constantName): void
            {
                $this->constantName = $constantName;
            }

            /** @return Node\Stmt\Const_|Node\Expr\FuncCall|null */
            public function getNode(): Node|null
            {
                return $this->node;
            }
        };
    }
}
