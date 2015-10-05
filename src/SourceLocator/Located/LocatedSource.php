<?php

namespace BetterReflection\SourceLocator\Located;

use BetterReflection\SourceLocator\Exception\InvalidFileLocation;

/**
 * Value object containing source code that has been located.
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
     * @param string      $source
     * @param string|null $filename
     */
    public function __construct($source, $filename)
    {
        if (!is_string($source) || empty($source)) {
            throw new \InvalidArgumentException(
                'Source code must be a non-empty string'
            );
        }

        if (!is_string($filename) && null !== $filename) {
            throw new \InvalidArgumentException(
                'Filename must be a string or null'
            );
        }

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
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @return null|string
     */
    public function getFileName()
    {
        return $this->filename;
    }

    /**
     * Is the located source in PHP internals?
     *
     * @return bool
     */
    public function isInternal()
    {
        return false;
    }

    /**
     * Is the located source produced by eval() or function_create()?
     *
     * @return bool
     */
    public function isEvaled()
    {
        return false;
    }
}
