<?php

namespace Roave\BetterReflection\SourceLocator\Type;

use Roave\BetterReflection\Identifier\IdentifierType;
use Roave\BetterReflection\SourceLocator\Located\LocatedSource;
use Composer\Autoload\ClassLoader;
use Roave\BetterReflection\Identifier\Identifier;

/**
 * This source locator uses Composer's built-in ClassLoader to locate files.
 *
 * Note that we use ClassLoader->findFile directory, rather than
 * ClassLoader->loadClass because this library has a strict requirement that we
 * do NOT actually load the classes
 */
class ComposerSourceLocator extends AbstractSourceLocator
{
    /**
     * @var ClassLoader
     */
    private $classLoader;

    public function __construct(ClassLoader $classLoader, Locator $locator = null)
    {
        parent::__construct($locator);
        $this->classLoader = $classLoader;
    }

    /**
     * {@inheritDoc}
     */
    protected function createLocatedSource(Identifier $identifier)
    {
        if ($identifier->getType()->getName() !== IdentifierType::IDENTIFIER_CLASS) {
            return null;
        }

        $filename = $this->classLoader->findFile($identifier->getName());

        if (!$filename) {
            return null;
        }

        return new LocatedSource(
            file_get_contents($filename),
            $filename
        );
    }
}
