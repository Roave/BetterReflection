<?php

namespace BetterReflection\SourceLocator\Type;

use BetterReflection\Identifier\Identifier;
use BetterReflection\SourceLocator\Exception\InvalidFileLocation;
use BetterReflection\SourceLocator\Located\LocatedSource;

/**
 * This source locator loads an entire file, specified in the constructor
 * argument.
 *
 * This is useful for loading a class that does not have a namespace. This is
 * also the class required if you want to use Reflector->getClassesFromFile
 * (which loads all classes from specified file)
 */
class SingleFileSourceLocator extends AbstractSourceLocator
{
    /**
     * @var string
     */
    private $filename;

    /**
     * @param string $filename
     */
    public function __construct($filename)
    {
        parent::__construct();
        $this->filename = (string)$filename;

        if (empty($this->filename)) {
            throw new InvalidFileLocation('Filename was empty');
        }

        if (!file_exists($this->filename)) {
            throw new InvalidFileLocation('File does not exist');
        }

        if (!is_file($this->filename)) {
            throw new InvalidFileLocation('Is not a file: ' . $this->filename);
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function createLocatedSource(Identifier $identifier)
    {
        return new LocatedSource(
            file_get_contents($this->filename),
            $this->filename
        );
    }
}
