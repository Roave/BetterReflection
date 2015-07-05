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
}
