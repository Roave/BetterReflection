<?php

namespace Roave\BetterReflection\Context;

use phpDocumentor\Reflection\Types\ContextFactory;

class PhpDocumentorContextFactory
{
    /**
     * @var PhpDocumentorContextFactory
     */
    private $innerFactory;

    public function __construct(ContextFactory $innerFactory)
    {
        $this->innerFactory = $innerFactory;
    }

    /**
     * {@inheritDoc}
     */
    public function createForNamespace($namespace, $fileContents)
    {
        return $this->innerFactory->createForNamespace($namespace, $fileContents);
    }
}
