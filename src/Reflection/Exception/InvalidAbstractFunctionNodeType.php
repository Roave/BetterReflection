<?php

namespace BetterReflection\Reflection\Exception;

use BetterReflection\Reflection\ReflectionFunctionAbstract;
use PhpParser\Node;

class InvalidAbstractFunctionNodeType extends \InvalidArgumentException
{
    /**
     * @param Node $node
     *
     * @return self
     */
    public static function fromNode(Node $node)
    {
        return new self(sprintf(
            'Node for "%s" must be "%s" or "%s", was a "%s"',
            ReflectionFunctionAbstract::class,
            Node\Stmt\ClassMethod::class,
            Node\FunctionLike::class,
            get_class($node)
        ));
    }
}
