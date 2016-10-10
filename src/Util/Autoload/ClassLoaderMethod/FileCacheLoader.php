<?php

namespace BetterReflection\Util\Autoload\ClassLoaderMethod;

use BetterReflection\Reflection\ReflectionClass;
use BetterReflection\Util\Autoload\ClassPrinter\ClassPrinterInterface;

class FileCacheLoader implements LoaderMethodInterface
{
    /**
     * @var string
     */
    private $cacheDirectory;

    /**
     * @var ClassPrinterInterface
     */
    private $classPrinter;

    /**
     * @param string $cacheDirectory
     * @param ClassPrinterInterface $classPrinter
     */
    public function __construct($cacheDirectory, ClassPrinterInterface $classPrinter)
    {
        $this->cacheDirectory = $cacheDirectory;
        $this->classPrinter = $classPrinter;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke(ReflectionClass $classInfo)
    {
        // @todo seems reasonable, any better approaches?
        $filename = $this->cacheDirectory . '/' . sha1($classInfo->getName());

        if (!file_exists($filename)) {
            file_put_contents($filename, $this->classPrinter->__invoke($classInfo));
        }

        // @todo we probably don't trust what's in this file, so maybe we need to verify contents are expected?
        /** @noinspection PhpIncludeInspection */
        require_once $filename;
    }
}
