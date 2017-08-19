<?php
declare(strict_types=1);

namespace Roave\BetterReflection\SourceLocator\Type;

use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\SourceLocator\FileChecker;
use Roave\BetterReflection\SourceLocator\Located\LocatedSource;

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
     * @throws \Roave\BetterReflection\SourceLocator\Exception\InvalidFileLocation
     */
    public function __construct(string $filename)
    {
        parent::__construct();

        FileChecker::checkFile($filename);

        $this->filename = $filename;
    }

    /**
     * {@inheritDoc}
     * @throws \InvalidArgumentException
     * @throws \Roave\BetterReflection\SourceLocator\Exception\InvalidFileLocation
     */
    protected function createLocatedSource(Identifier $identifier) : ?LocatedSource
    {
        return new LocatedSource(
            \file_get_contents($this->filename),
            $this->filename
        );
    }
}
