<?php
declare(strict_types=1);

namespace Roave\BetterReflection\SourceLocator\Located;

use Roave\BetterReflection\SourceLocator\FileChecker;
use Roave\BetterReflection\Util\FileHelper;

/**
 * Value object containing source code that has been located.
 *
 * @internal
 */
class LocatedSource
{
    /**
     * @var string
     */
    private $source;

    /**
     * @var string|null
     */
    private $filename;

    /**
     * @param string $source
     * @param string|null $filename
     * @throws \InvalidArgumentException
     * @throws \Roave\BetterReflection\SourceLocator\Exception\InvalidFileLocation
     */
    public function __construct(string $source, ?string $filename)
    {
        if (null !== $filename) {
            FileChecker::assertReadableFile($filename);

            $filename = FileHelper::normalizeWindowsPath($filename);
        }

        $this->source   = $source;
        $this->filename = $filename;
    }

    /**
     * @return string
     */
    public function getSource() : string
    {
        return $this->source;
    }

    /**
     * @return null|string
     */
    public function getFileName() : ?string
    {
        return $this->filename;
    }

    /**
     * Is the located source in PHP internals?
     *
     * @return bool
     */
    public function isInternal() : bool
    {
        return false;
    }

    public function getExtensionName() : ?string
    {
        return null;
    }

    /**
     * Is the located source produced by eval() or \function_create()?
     *
     * @return bool
     */
    public function isEvaled() : bool
    {
        return false;
    }
}
