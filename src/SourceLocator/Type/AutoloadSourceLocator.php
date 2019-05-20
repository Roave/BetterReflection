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
use Roave\BetterReflection\SourceLocator\Located\LocatedSource;
use Roave\BetterReflection\Util\ConstantNodeChecker;
use const STREAM_URL_STAT_QUIET;
use function array_key_exists;
use function class_exists;
use function defined;
use function file_exists;
use function file_get_contents;
use function function_exists;
use function get_defined_constants;
use function get_included_files;
use function interface_exists;
use function is_string;
use function restore_error_handler;
use function set_error_handler;
use function stat;
use function stream_wrapper_register;
use function stream_wrapper_restore;
use function stream_wrapper_unregister;
use function trait_exists;

/**
 * Use PHP's built in autoloader to locate a class, without actually loading.
 *
 * There are some prerequisites...
 *   - we expect the autoloader to load classes from a file (i.e. using require/include)
 */
class AutoloadSourceLocator extends AbstractSourceLocator
{
    /** @var AstLocator */
    private $astLocator;

    /** @var Parser */
    private $phpParser;

    /** @var NodeTraverser */
    private $nodeTraverser;

    /** @var NodeVisitorAbstract */
    private $constantVisitor;

    /**
     * Note: the constructor has been made a 0-argument constructor because `\stream_wrapper_register`
     *       is a piece of trash, and doesn't accept instances, just class names.
     */
    public function __construct(?AstLocator $astLocator = null, ?Parser $phpParser = null)
    {
        $betterReflection = new BetterReflection();

        $validLocator = $astLocator ?? self::$currentAstLocator ?? $betterReflection->astLocator();

        parent::__construct($validLocator);

        $this->astLocator      = $validLocator;
        $this->phpParser       = $phpParser ?? $betterReflection->phpParser();
        $this->constantVisitor = $this->createConstantVisitor();

        $this->nodeTraverser = new NodeTraverser();
        $this->nodeTraverser->addVisitor(new NameResolver());
        $this->nodeTraverser->addVisitor($this->constantVisitor);
    }

    /**
     * Primarily used by the non-loading-autoloader magic trickery to determine
     * the filename used during autoloading.
     *
     * @var string|null
     */
    private static $autoloadLocatedFile;

    /** @var AstLocator */
    private static $currentAstLocator;

    /**
     * {@inheritDoc}
     *
     * @throws InvalidArgumentException
     * @throws InvalidFileLocation
     */
    protected function createLocatedSource(Identifier $identifier) : ?LocatedSource
    {
        $potentiallyLocatedFile = $this->attemptAutoloadForIdentifier($identifier);

        if (! ($potentiallyLocatedFile && file_exists($potentiallyLocatedFile))) {
            return null;
        }

        return new LocatedSource(
            file_get_contents($potentiallyLocatedFile),
            $potentiallyLocatedFile
        );
    }

    /**
     * Attempts to locate the specified identifier.
     *
     * @throws ReflectionException
     */
    private function attemptAutoloadForIdentifier(Identifier $identifier) : ?string
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
     * @throws ReflectionException
     */
    private function locateClassByName(string $className) : ?string
    {
        if (class_exists($className, false) || interface_exists($className, false) || trait_exists($className, false)) {
            $filename = (new ReflectionClass($className))->getFileName();

            if (! is_string($filename)) {
                return null;
            }

            return $filename;
        }

        self::$autoloadLocatedFile = null;
        self::$currentAstLocator   = $this->astLocator; // passing the locator on to the implicitly instantiated `self`
        $previousErrorHandler      = set_error_handler(static function () : void {
        });
        stream_wrapper_unregister('file');
        stream_wrapper_register('file', self::class);
        class_exists($className);
        stream_wrapper_restore('file');
        set_error_handler($previousErrorHandler);

        return self::$autoloadLocatedFile;
    }

    /**
     * We can only load functions if they already exist, because PHP does not
     * have function autoloading. Therefore if it exists, we simply use the
     * internal reflection API to find the filename. If it doesn't we can do
     * nothing so throw an exception.
     *
     * @throws ReflectionException
     */
    private function locateFunctionByName(string $functionName) : ?string
    {
        if (! function_exists($functionName)) {
            return null;
        }

        $reflection         = new ReflectionFunction($functionName);
        $reflectionFileName = $reflection->getFileName();

        if (! is_string($reflectionFileName)) {
            return null;
        }

        return $reflectionFileName;
    }

    /**
     * We can only load constants if they already exist, because PHP does not
     * have constant autoloading. Therefore if it exists, we simply use brute force
     * to search throught all included files to find the right filename.
     */
    private function locateConstantByName(string $constantName) : ?string
    {
        if (! defined($constantName)) {
            return null;
        }

        if (! array_key_exists($constantName, get_defined_constants(true)['user'])) {
            return null;
        }

        /** @psalm-suppress UndefinedMethod */
        $this->constantVisitor->setConstantName($constantName);

        $constantFileName = null;

        foreach (get_included_files() as $includedFileName) {
            $ast = $this->phpParser->parse(file_get_contents($includedFileName));

            $this->nodeTraverser->traverse($ast);

            /** @psalm-suppress UndefinedMethod */
            if ($this->constantVisitor->getNode() !== null) {
                $constantFileName = $includedFileName;
                break;
            }
        }

        return $constantFileName;
    }

    /**
     * Our wrapper simply records which file we tried to load and returns
     * boolean false indicating failure.
     *
     * @see https://php.net/manual/en/class.streamwrapper.php
     * @see https://php.net/manual/en/streamwrapper.stream-open.php
     *
     * @param string $path
     * @param string $mode
     * @param int    $options
     * @param string $opened_path
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
     */
    public function stream_open($path, $mode, $options, &$opened_path) : bool
    {
        self::$autoloadLocatedFile = $path;

        return false;
    }

    /**
     * url_stat is triggered by calls like "file_exists". The call to "file_exists" must not be overloaded.
     * This function restores the original "file" stream, issues a call to "stat" to get the real results,
     * and then re-registers the AutoloadSourceLocator stream wrapper.
     *
     * @see https://php.net/manual/en/class.streamwrapper.php
     * @see https://php.net/manual/en/streamwrapper.url-stat.php
     *
     * @param string $path
     * @param int    $flags
     *
     * @return mixed[]|bool
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
     */
    public function url_stat($path, $flags)
    {
        stream_wrapper_restore('file');

        if ($flags & STREAM_URL_STAT_QUIET) {
            set_error_handler(static function () {
                // Use native error handler
                return false;
            });
            $result = @stat($path);
            restore_error_handler();
        } else {
            $result = stat($path);
        }

        stream_wrapper_unregister('file');
        stream_wrapper_register('file', self::class);

        return $result;
    }

    private function createConstantVisitor() : NodeVisitorAbstract
    {
        return new class() extends NodeVisitorAbstract
        {
            /** @var string|null */
            private $constantName;

            /** @var Node\Stmt\Const_|Node\Expr\FuncCall|null */
            private $node;

            public function enterNode(Node $node) : ?int
            {
                if ($node instanceof Node\Stmt\Const_) {
                    foreach ($node->consts as $constNode) {
                        /** @psalm-suppress UndefinedPropertyFetch */
                        if ($constNode->namespacedName->toString() === $this->constantName) {
                            $this->node = $node;

                            return NodeTraverser::STOP_TRAVERSAL;
                        }
                    }

                    return NodeTraverser::DONT_TRAVERSE_CHILDREN;
                }

                if ($node instanceof Node\Expr\FuncCall) {
                    try {
                        ConstantNodeChecker::assertValidDefineFunctionCall($node);
                    } catch (InvalidConstantNode $e) {
                        return null;
                    }

                    /** @var Node\Scalar\String_ $nameNode */
                    $nameNode = $node->args[0]->value;

                    if ($nameNode->value === $this->constantName) {
                        $this->node = $node;

                        return NodeTraverser::STOP_TRAVERSAL;
                    }
                }

                if ($node instanceof Node\Stmt\Class_) {
                    return NodeTraverser::DONT_TRAVERSE_CHILDREN;
                }

                return null;
            }

            public function setConstantName(string $constantName) : void
            {
                $this->constantName = $constantName;
            }

            /**
             * @return Node\Stmt\Const_|Node\Expr\FuncCall|null
             */
            public function getNode() : ?Node
            {
                return $this->node;
            }
        };
    }
}
