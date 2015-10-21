<?php

namespace BetterReflection\SourceLocator\Type;

use BetterReflection\SourceLocator\Exception\FunctionUndefined;
use BetterReflection\Identifier\Identifier;
use BetterReflection\SourceLocator\Located\LocatedSource;

/**
 * Use PHP's built in autoloader to locate a class, without actually loading.
 *
 * There are some prerequisites...
 *   - we expect the autoloader to load classes from a file (i.e. using require/include)
 */
class AutoloadSourceLocator extends AbstractSourceLocator
{
    /**
     * Primarily used by the non-loading-autoloader magic trickery to determine
     * the filename used during autoloading.
     *
     * @var string|null
     */
    private static $autoloadLocatedFile;

    /**
     * {@inheritDoc}
     */
    protected function createLocatedSource(Identifier $identifier)
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
     * @param Identifier $identifier
     * @return string
     */
    private function attemptAutoloadForIdentifier(Identifier $identifier)
    {
        if ($identifier->isClass()) {
            return $this->locateClassByName($identifier->getName());
        }

        if ($identifier->isFunction()) {
            return $this->locateFunctionByName($identifier->getName());
        }
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
     * @param string $className
     * @return string
     */
    private function locateClassByName($className)
    {
        if (class_exists($className, false) || interface_exists($className, false) || trait_exists($className, false)) {
            return (new \ReflectionClass($className))->getFileName();
        }

        self::$autoloadLocatedFile = null;
        $previousErrorHandler = set_error_handler(function () {});
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
     * @param string $functionName
     * @return string
     * @throws FunctionUndefined
     */
    private function locateFunctionByName($functionName)
    {
        if (!function_exists($functionName)) {
            throw new FunctionUndefined('Function ' . $functionName . ' was not already defined');
        }

        $reflection = new \ReflectionFunction($functionName);
        return $reflection->getFileName();
    }

    /**
     * Our wrapper simply records which file we tried to load and returns
     * boolean false indicating failure.
     *
     * @param string $path
     * @param string $mode
     * @param int $options
     * @param string $opened_path
     * @return bool
     * @see http://php.net/manual/en/class.streamwrapper.php
     * @see http://php.net/manual/en/streamwrapper.stream-open.php
     */
    public function stream_open($path, $mode, $options, &$opened_path)
    {
        self::$autoloadLocatedFile = $path;
        return false;
    }

    /**
     * Must be implemented to return some data so that calls like is_file will work.
     *
     * @param $path
     * @param $flags
     * @return mixed[]
     * @see http://php.net/manual/en/class.streamwrapper.php
     * @see http://php.net/manual/en/streamwrapper.url-stat.php
     */
    public function url_stat($path, $flags)
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
