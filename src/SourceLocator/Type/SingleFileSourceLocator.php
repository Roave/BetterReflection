<?php
declare(strict_types=1);

namespace Rector\BetterReflection\SourceLocator\Type;

use Rector\BetterReflection\Identifier\Identifier;
use Rector\BetterReflection\SourceLocator\Ast\Locator;
use Rector\BetterReflection\SourceLocator\FileChecker;
use Rector\BetterReflection\SourceLocator\Located\LocatedSource;

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
    private $fileName;

    /**
     * @throws \Rector\BetterReflection\SourceLocator\Exception\InvalidFileLocation
     */
    public function __construct(string $fileName, Locator $astLocator)
    {
        FileChecker::assertReadableFile($fileName);

        parent::__construct($astLocator);

        $this->fileName = $fileName;
    }

    /**
     * {@inheritDoc}
     * @throws \InvalidArgumentException
     * @throws \Rector\BetterReflection\SourceLocator\Exception\InvalidFileLocation
     */
    protected function createLocatedSource(Identifier $identifier) : ?LocatedSource
    {
        return new LocatedSource(
            \file_get_contents($this->fileName),
            $this->fileName
        );
    }
}
