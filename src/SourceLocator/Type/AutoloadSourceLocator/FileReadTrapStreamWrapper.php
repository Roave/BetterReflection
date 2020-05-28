<?php

declare(strict_types=1);

namespace Roave\BetterReflection\SourceLocator\Type\AutoloadSourceLocator;

use LogicException;
use function stat;
use function stream_wrapper_register;
use function stream_wrapper_restore;
use function stream_wrapper_unregister;
use const STREAM_URL_STAT_QUIET;

/**
 * This class will operate as a stream wrapper, intercepting any access to a file while
 * in operation.
 *
 * @internal DO NOT USE: this is an implementation detail of
 *           the {@see \Roave\BetterReflection\SourceLocator\Type\AutoloadSourceLocator}
 *
 * phpcs:disable SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
 * phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
 * phpcs:disable Squiz.NamingConventions.ValidVariableName.NotCamelCaps
 */
final class FileReadTrapStreamWrapper
{
    private const DEFAULT_STREAM_WRAPPER_PROTOCOLS = [
        'file',
        'phar',
    ];

    /** @var string[]|null */
    private static $registeredStreamWrapperProtocols;

    /**
     * Read this property to determine the last file on which reads were attempted
     *
     * @var string|null
     * @psalm-readonly
     * @psalm-allow-private-mutation
     */
    public static $autoloadLocatedFile;

    /**
     * @param callable() : ExecutedMethodReturnType $executeMeWithinStreamWrapperOverride
     * @param string[]                              $streamWrapperProtocols
     *
     * @return mixed
     *
     * @psalm-template ExecutedMethodReturnType of mixed
     * @psalm-return ExecutedMethodReturnType
     */
    public static function withStreamWrapperOverride(
        callable $executeMeWithinStreamWrapperOverride,
        array $streamWrapperProtocols = self::DEFAULT_STREAM_WRAPPER_PROTOCOLS
    ) {
        self::$registeredStreamWrapperProtocols = $streamWrapperProtocols;
        self::$autoloadLocatedFile              = null;

        try {
            foreach ($streamWrapperProtocols as $protocol) {
                stream_wrapper_unregister($protocol);
                stream_wrapper_register($protocol, self::class);
            }

            $result = $executeMeWithinStreamWrapperOverride();
        } finally {
            foreach ($streamWrapperProtocols as $protocol) {
                stream_wrapper_restore($protocol);
            }
        }

        self::$registeredStreamWrapperProtocols = null;
        self::$autoloadLocatedFile              = null;

        return $result;
    }

    /**
     * Our wrapper simply records which file we tried to load and returns
     * boolean false indicating failure.
     *
     * @internal do not call this method directly! This is stream wrapper
     *           voodoo logic that you **DO NOT** want to touch!
     *
     * @see https://php.net/manual/en/class.streamwrapper.php
     * @see https://php.net/manual/en/streamwrapper.stream-open.php
     *
     * @param string $path
     * @param string $mode
     * @param int    $options
     * @param string $opened_path
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
     * @internal do not call this method directly! This is stream wrapper
     *           voodoo logic that you **DO NOT** want to touch!
     *
     * @see https://php.net/manual/en/class.streamwrapper.php
     * @see https://php.net/manual/en/streamwrapper.url-stat.php
     *
     * @param string $path
     * @param int    $flags
     *
     * @return mixed[]|bool
     */
    public function url_stat($path, $flags)
    {
        if (self::$registeredStreamWrapperProtocols === null) {
            throw new LogicException(self::class . ' not registered: cannot operate. Do not call this method directly.');
        }

        foreach (self::$registeredStreamWrapperProtocols as $protocol) {
            stream_wrapper_restore($protocol);
        }

        if ($flags & STREAM_URL_STAT_QUIET) {
            $result = @stat($path);
        } else {
            $result = stat($path);
        }

        foreach (self::$registeredStreamWrapperProtocols as $protocol) {
            stream_wrapper_unregister($protocol);
            stream_wrapper_register($protocol, self::class);
        }

        return $result;
    }
}
