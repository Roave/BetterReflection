<?php

namespace Roave\BetterReflection\Context;

interface ContextFactory
{
    /**
     * Build a Context for a namespace in the provided file contents.
     *
     * @param string $namespace It does not matter if a `\` precedes the namespace name, this method first normalizes.
     * @param string $fileContents the file's contents to retrieve the aliases from with the given namespace.
     *
     * @see Context for more information on Contexts.
     *
     * @return Context
     */
    public function createForNamespace($namespace, $fileContents);
}
