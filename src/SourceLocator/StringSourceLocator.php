<?php

namespace BetterReflection\SourceLocator;

class StringSourceLocator implements SourceLocator
{
    /**
     * @var string
     */
    private $source;

    public function __construct($source)
    {
        $this->source = (string)$source;

        if (empty($this->source)) {
            throw new \InvalidArgumentException('Source code string was empty');
        }
    }

    /**
     * @param string $className
     * @return LocatedSource
     */
    public function locate($className)
    {
        return new LocatedSource($this->source, null);
    }
}
