<?php

namespace BetterReflection\SourceLocator;

class FilenameSourceLocator implements SourceLocator
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
     * @param string $className
     * @return LocatedSource
     */
    public function locate($className)
    {
        return new LocatedSource(
            file_get_contents($this->filename),
            $this->filename
        );
    }
}
