<?php

namespace Roave\BetterReflection\SourceLocator\Located;

use Roave\BetterReflection\SourceLocator\Exception\InvalidFileLocation;

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
            if (empty($filename)) {
                throw new InvalidFileLocation('Filename was empty');
            }

            if (!file_exists($filename)) {
                throw new InvalidFileLocation('File does not exist');
            }

            if (!is_readable($filename)) {
                throw new InvalidFileLocation('File is not readable');
            }

            if (!is_file($filename)) {
                throw new InvalidFileLocation('Is not a file: ' . $filename);
            }
        }

        $this->source = $source;
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

    /**
     * Is the located source produced by eval() or function_create()?
     *
     * @return bool
     */
    public function isEvaled() : bool
    {
        return false;
    }
}
