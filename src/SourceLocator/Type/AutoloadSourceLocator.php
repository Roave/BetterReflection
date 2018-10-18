<?php

declare(strict_types=1);

namespace Roave\BetterReflection\SourceLocator\Type;

use InvalidArgumentException;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\SourceLocator\Ast\Locator as AstLocator;
use Roave\BetterReflection\SourceLocator\Exception\InvalidFileLocation;
use Roave\BetterReflection\SourceLocator\Located\LocatedSource;
use const STREAM_URL_STAT_QUIET;
use function class_exists;
use function file_exists;
use function file_get_contents;
use function function_exists;
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
}
