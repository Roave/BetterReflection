<?php

namespace BetterReflection\SourceLocator;

use Composer\Autoload\ClassLoader;
use BetterReflection\Reflection\Symbol;

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
     * @param Symbol $symbol
     * @return LocatedSource
     */
    public function __invoke(Symbol $symbol)
    {
        if ($symbol->getType() !== Symbol::SYMBOL_CLASS) {
            throw new \LogicException(__CLASS__ . ' can only be used to locate classes');
        }

        $filename = $this->classLoader->findFile($symbol->getName());

        if (!$filename) {
            throw new \UnexpectedValueException(sprintf(
                'Could not locate file to load "%s"', $symbol->getName()
            ));
        }

        return new LocatedSource(
            file_get_contents($filename),
            $filename
        );
    }
}
