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
class SingleFileSourceLocator implements SourceLocator
{
    /**
     * @var string
     */
    private $filename;

    public function __construct($filename)
    {
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
     * @param Identifier $identifier
     * @return LocatedSource
     */
    public function __invoke(Identifier $identifier)
    {
        return new LocatedSource(
            file_get_contents($this->filename),
            $this->filename
        );
    }
}
