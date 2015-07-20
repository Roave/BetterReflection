<?php

namespace BetterReflection\SourceLocator;

/**
 * Value object containing source code that has been located.
 */
class LocatedSource
{
    const INTERNAL_SOURCE_MAGIC_CONST = 'BetterReflectionPhpInternalSourceStub';

    /**
     * @var string
     */
    private $source;

    /**
     * @var string|null
     */
    private $filename;

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

        if (null !== $filename && self::INTERNAL_SOURCE_MAGIC_CONST !== $filename) {
            if (empty($filename)) {
                throw new Exception\InvalidFileLocation('Filename was empty');
            }

            if (!file_exists($filename)) {
                throw new Exception\InvalidFileLocation('File does not exist');
            }

            if (!is_readable($filename)) {
                throw new Exception\InvalidFileLocation('File is not readable');
            }

            if (!is_file($filename)) {
                throw new Exception\InvalidFileLocation('Is not a file: ' . $filename);
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
        return $this->filename === self::INTERNAL_SOURCE_MAGIC_CONST;
    }
}
