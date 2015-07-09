<?php

namespace BetterReflection\Reflection;

use BetterReflection\Reflector\FunctionReflector;
use BetterReflection\SourceLocator\AutoloadSourceLocator;
use BetterReflection\SourceLocator\LocatedSource;
use PhpParser\Node\Stmt\Function_ as FunctionNode;
use PhpParser\Node\Stmt\Namespace_ as NamespaceNode;

class ReflectionFunction extends ReflectionFunctionAbstract implements Reflection
{
    /**
     * @param string $functionName
     * @return ReflectionFunction
     */
    public static function createFromName($functionName)
    {
        return (new FunctionReflector(new AutoloadSourceLocator()))->reflect($functionName);
    }

    /**
     * @param FunctionNode $node
     * @param NamespaceNode|null $namespaceNode
     * @param LocatedSource $locatedSource
     * @return ReflectionFunction
     */
    public static function createFromNode(
        FunctionNode $node,
        LocatedSource $locatedSource,
        NamespaceNode $namespaceNode = null
    ) {
        $function = new self();

        $function->populateFunctionAbstract($node, $locatedSource, $namespaceNode);

        return $function;
    }

    /**
     * Check to see if this function has been disabled (by the PHP INI file
     * directive `disable_functions`)
     *
     * Note - we cannot reflect on internal functions (as there is no PHP source
     * code we can access. This means, at present, we can only EVER return false
     * from this function, because you cannot disable user-defined functions.
     *
     * @todo https://github.com/Roave/BetterReflection/issues/14
     * @see http://php.net/manual/en/ini.core.php#ini.disable-functions
     * @return bool
     */
    public function isDisabled()
    {
        return false;
    }
}
