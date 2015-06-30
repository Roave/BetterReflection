<?php

namespace BetterReflection\SourceLocator;

use Composer\Autoload\ClassLoader;

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
     * @param string $className
     * @return LocatedSource
     */
    public function __invoke($className)
    {
        $filename = $this->classLoader->findFile($className);

        if (!$filename) {
            throw new \UnexpectedValueException(sprintf(
                'Could not locate file to load "%s"', $className
            ));
        }

        return new LocatedSource(
            file_get_contents($filename),
            $filename
        );
    }
}
