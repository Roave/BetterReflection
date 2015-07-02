<?php

namespace BetterReflection\SourceLocator;

use BetterReflection\Identifier\IdentifierType;
use Composer\Autoload\ClassLoader;
use BetterReflection\Identifier\Identifier;

/**
 * This source locator uses Composer's built-in ClassLoader to locate files.
 *
 * Note that we use ClassLoader->findFile directory, rather than
 * ClassLoader->loadClass because this library has a strict requirement that we
 * do NOT actually load the classes
 */
class ComposerSourceLocator implements SourceLocator
{
    /**
     * @var ClassLoader
     */
    private $classLoader;

    public function __construct(ClassLoader $classLoader)
    {
        $this->classLoader = $classLoader;
    }

    /**
     * @param Identifier $identifier
     * @return LocatedSource
     */
    public function __invoke(Identifier $identifier)
    {
        if ($identifier->getType()->getName() !== IdentifierType::IDENTIFIER_CLASS) {
            throw new \LogicException(__CLASS__ . ' can only be used to locate classes');
        }

        $filename = $this->classLoader->findFile($identifier->getName());

        if (!$filename) {
            throw new \UnexpectedValueException(sprintf(
                'Could not locate file to load "%s"', $identifier->getName()
            ));
        }

        return new LocatedSource(
            file_get_contents($filename),
            $filename
        );
    }
}
