<?php

namespace BetterReflection\SourceLocator;

use Composer\Autoload\ClassLoader;

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
    public function locate($className)
    {
        $filename = $this->classLoader->findFile($className);

        if (!$filename) {
            throw new \UnexpectedValueException(sprintf('Could not locate file to load "%s"', $className));
        }

        return new LocatedSource(
            file_get_contents($filename),
            $filename
        );
    }
}
