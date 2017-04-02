<?php

namespace Roave\BetterReflection\Context;

use phpDocumentor\Reflection\Types\ContextFactory as InnerFactory;

class PhpDocumentorContextFactory implements ContextFactory
{
    /**
     * @var InnerFactory
     */
    private $innerFactory;

    public function __construct()
    {
        $this->innerFactory = new InnerFactory();
    }

    /**
     * {@inheritDoc}
     */
    public function createForNamespace($namespace, $fileContents)
    {
        return $this->innerFactory->createForNamespace($namespace, $fileContents);
    }
}
