<?php

declare(strict_types=1);

namespace Roave\BetterReflection\SourceLocator\Type;

use ReflectionClass;
use ReflectionFunction;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\SourceLocator\Ast\Locator as AstLocator;
use Roave\BetterReflection\SourceLocator\Located\LocatedSource;
use function array_merge;
use function array_values;
use function class_exists;
use function file_exists;
use function file_get_contents;
use function function_exists;
use function interface_exists;
use function is_string;
use function set_error_handler;
use function stream_wrapper_register;
use function stream_wrapper_restore;
use function stream_wrapper_unregister;
use function time;
use function trait_exists;

/**
 * Use PHP's built in autoloader to locate a class, without actually loading.
 *
 * There are some prerequisites...
 *   - we expect the autoloader to load classes from a file (i.e. using require/include)
 */
class AutoloadSourceLocator extends AbstractSourceLocator
{
    /**
     * @var AstLocator
     */
    private $astLocator;

    /**
     * Note: the constructor has been made a 0-argument constructor because `\stream_wrapper_register`
     *       is a piece of trash, and doesn't accept instances, just class names.
     */
    public function __construct(?AstLocator $astLocator = null)
    {
        $validLocator = $astLocator ?? self::$currentAstLocator ?? (new BetterReflection())->astLocator();

        parent::__construct($validLocator);

        $this->astLocator = $validLocator;
    }

    /**
     * Primarily used by the non-loading-autoloader magic trickery to determine
     * the filename used during autoloading.
     *
     * @var string|null
     */
    private static $autoloadLocatedFile;

    /**
     * @var AstLocator
     */
    private static $currentAstLocator;

    /**
     * {@inheritDoc}
     * @throws \InvalidArgumentException
     * @throws \Roave\BetterReflection\SourceLocator\Exception\InvalidFileLocation
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
     * @throws \ReflectionException
     */
    private function attemptAutoloadForIdentifier(Identifier $identifier) : ?string
    {
        if ($identifier->isClass()) {
            return $this->locateClassByName($identifier->getName());
        }

        if ($identifier->isFunction()) {
            return $this->locateFunctionByName($identifier->getName());
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
     * @throws \ReflectionException
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
        $previousErrorHandler      = set_error_handler(function () : void {
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
     * @throws \ReflectionException
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
     * Our wrapper simply records which file we tried to load and returns
     * boolean false indicating failure.
     *
     * @param string $path
     * @param string $mode
     * @param int    $options
     * @param string $opened_path
     * @see http://php.net/manual/en/class.streamwrapper.php
     * @see http://php.net/manual/en/streamwrapper.stream-open.php
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
     */
    public function stream_open($path, $mode, $options, &$opened_path) : bool
    {
        self::$autoloadLocatedFile = $path;
        return false;
    }

    /**
     * Must be implemented to return some data so that calls like is_file will work.
     *
     * @param string $path
     * @param int    $flags
     * @return mixed[]
     * @see http://php.net/manual/en/class.streamwrapper.php
     * @see http://php.net/manual/en/streamwrapper.url-stat.php
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
     */
    public function url_stat($path, $flags) : array
    {
        // This is just dummy file stat data to fool stat calls
        $assoc = [
            'dev' => 2056,
            'ino' => 19679399,
            'mode' => 33204,
            'nlink' => 1,
            'uid' => 1000,
            'gid' => 1000,
            'rdev' => 0,
            'size' => 1,
            'atime' => time(),
            'mtime' => time(),
            'ctime' => time(),
            'blksize' => 4096,
            'blocks' => 8,
        ];

        return array_merge(array_values($assoc), $assoc);
    }
}
