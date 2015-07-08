<?php

namespace BetterReflection\Reflection;

use PhpParser\Node\Stmt\Function_ as FunctionNode;
use PhpParser\Node\Stmt\Namespace_ as NamespaceNode;

class ReflectionFunction extends ReflectionFunctionAbstract implements Reflection
{
    /**
     * @param FunctionNode $node
     * @param NamespaceNode|null $namespaceNode
     * @param string|null $filename
     * @return ReflectionMethod
     */
    public static function createFromNode(
        FunctionNode $node,
        NamespaceNode $namespaceNode = null,
        $filename = null
    ) {
        $method = new self($node);

        $method->populateFunctionAbstract($node, $namespaceNode, $filename);

        return $method;
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
