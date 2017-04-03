<?php

namespace Roave\BetterReflection\Reflection\Exception;

use Roave\BetterReflection\Reflection\ReflectionFunctionAbstract;
use PhpParser\Node;

class InvalidAbstractFunctionNodeType extends \InvalidArgumentException
{
    /**
     * @param Node $node
     *
     * @return self
     */
    public static function fromNode(Node $node) : self
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
