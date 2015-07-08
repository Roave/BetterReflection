<?php

namespace BetterReflection\SourceLocator;

use BetterReflection\Identifier\Identifier;
use BetterReflection\Identifier\IdentifierType;

/**
 * Use PHP's built in autoloader to locate a class, without actually loading.
 *
 * There are some prerequisites...
 *   - we expect the autoloader to load classes from a file (i.e. using require/include)
 */
class AutoloadSourceLocator implements SourceLocator
{
    private static $autoloadLocatedFile;

    public function __invoke(Identifier $identifier)
    {
        self::$autoloadLocatedFile = null;

        $this->locateIdentifier($identifier);

        if (null == self::$autoloadLocatedFile) {
            throw new Exception\AutoloadFailure(sprintf(
                'Unable to autoload the %s called %s',
                $identifier->getType()->getName(),
                $identifier->getName()
            ));
        }

        return new LocatedSource(
            file_get_contents(self::$autoloadLocatedFile),
            self::$autoloadLocatedFile
        );
    }

    /**
     * Attempts to locate the specified identifier and store the result in
     * self::$autoloadLocatedFile
     *
     * @param Identifier $identifier
     */
    private function locateIdentifier(Identifier $identifier)
    {
        if ($identifier->getType()->getName() == IdentifierType::IDENTIFIER_CLASS) {
            $this->locateClassByName($identifier->getName());
        } elseif ($identifier->getType()->getName() == IdentifierType::IDENTIFIER_FUNCTION) {
            $this->locateFunctionByName($identifier->getName());
        } else {
            throw new Exception\UnloadableIdentifierType('AutoloadSourceLocator cannot locate ' . $identifier->getType()->getName());
        }
    }

    /**
     * Attempt to locate a class by name
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
     * @param string $className#
     */
    private function locateClassByName($className)
    {
        if (class_exists($className, false)) {
            $reflection = new \ReflectionClass($className);
            self::$autoloadLocatedFile = $reflection->getFileName();
        } else {
            $previousErrorHandler = set_error_handler(function () {});
            stream_wrapper_unregister('file');
            stream_wrapper_register('file', self::class);
            class_exists($className);
            stream_wrapper_restore('file');
            set_error_handler($previousErrorHandler);
        }
    }

    /**
     * We can only load functions if they already exist, because PHP does not
     * have function autoloading. Therefore if it exists, we simply use the
     * internal reflection API to find the filename. If it doesn't we can do
     * nothing so throw an exception.
     *
     * @param string $functionName
     * @throws Exception\FunctionUndefined
     */
    private function locateFunctionByName($functionName)
    {
        if (function_exists($functionName)) {
            $reflection = new \ReflectionFunction($functionName);
            self::$autoloadLocatedFile = $reflection->getFileName();
        } else {
            throw new Exception\FunctionUndefined('Function ' . $functionName . ' was not already defined');
        }
    }

    /**
     * Our wrapper simply records which file we tried to load and returns
     * boolean false indicating failure
     *
     * @param string $path
     * @param string $mode
     * @param int $options
     * @param string $opened_path
     * @return bool
     */
    public function stream_open($path, $mode, $options, &$opened_path)
    {
        self::$autoloadLocatedFile = $path;
        return false;
    }

    /**
     * Must be implemented to return some data so that calls like is_file will work
     *
     * @param $path
     * @param $flags
     * @return mixed[]
     */
    public function url_stat($path, $flags)
    {
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
