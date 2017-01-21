<?php

namespace Roave\BetterReflection\Context;

use Roave\BetterReflection\Context\ContextFactory;

class CachedContextFactory implements ContextFactory
{
    private $contexts = [];

    public function __construct(ContextFactory $innerFactory = null)
    {
        $this->innerFactory = $innerFactory ?: new PhpDocumentorContextFactory();
    }

    /**
     * {@inheritDoc}
     */
    public function createForNamespace($namespace, $fileContents)
    {
        $hash = $namespace . md5($fileContents);

        if (isset($this->contexts[$hash])) {
            return $this->contexts[$hash];
        }

        $this->contexts[$hash] = $this->innerFactory->createForNamespace($namespace, $fileContents);

        return $this->contexts[$hash];
    }
}
