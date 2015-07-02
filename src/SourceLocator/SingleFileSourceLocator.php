<?php

namespace BetterReflection\SourceLocator;

use BetterReflection\Reflection\Symbol;

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
            throw new \InvalidArgumentException('Filename was empty');
        }
    }

    /**
     * @param Symbol $symbol
     * @return LocatedSource
     */
    public function __invoke(Symbol $symbol)
    {
        return new LocatedSource(
            file_get_contents($this->filename),
            $this->filename
        );
    }
}
