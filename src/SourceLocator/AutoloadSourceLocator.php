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

        if ($identifier->getType()->getName() == IdentifierType::IDENTIFIER_CLASS) {
            if (class_exists($identifier->getName(), false)) {
                $reflection = new \ReflectionClass($identifier->getName());
                self::$autoloadLocatedFile = $reflection->getFileName();
            } else {
                $previousErrorHandler = set_error_handler(function () {});
                stream_wrapper_unregister('file');
                stream_wrapper_register('file', self::class);
                class_exists($identifier->getName());
                stream_wrapper_restore('file');
                set_error_handler($previousErrorHandler);
            }
        } else {
            throw new \LogicException('AutoloadSourceLocator can only locate classes, you asked for: ' . $identifier->getType()->getName());
        }

        if (null == self::$autoloadLocatedFile) {
            throw new \RuntimeException(sprintf(
                'Unable to autoload the %s called %s',
                $identifier->getType()->getDisplayName(),
                $identifier->getName()
            ));
        }

        return new LocatedSource(
            file_get_contents(self::$autoloadLocatedFile),
            self::$autoloadLocatedFile
        );
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

        $x = array_merge(array_values($assoc), $assoc);
        return $x;
    }
}
