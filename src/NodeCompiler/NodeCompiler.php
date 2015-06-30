<?php

namespace BetterReflection\NodeCompiler;

use PhpParser\Node;

class NodeCompiler
{
    /**
     * Compile an expression from a node into a value
     *
     * @param Node $node
     * @return mixed
     */
    public static function compile(Node $node)
    {
        $type = get_class($node);

        switch ($type) {
            case Node\Scalar\String_::class:
            case Node\Scalar\DNumber::class:
            case Node\Scalar\LNumber::class:
                return $node->value;
            case Node\Expr\Array_::class:
                return []; // @todo compile expression
            case Node\Expr\ConstFetch::class:
                if ($node->name->parts[0] == 'null') {
                    return null;
                } else if ($node->name->parts[0] == 'false') {
                    return false;
                } else if ($node->name->parts[0] == 'true') {
                    return true;
                } else {
                    // @todo this should evaluate the VALUE, not the name
                    return $node->name->parts[0];
                }
                break;
            case Node\Expr\ClassConstFetch::class:
                // @todo this should evaluate the VALUE, not the name
                $className = implode('\\', $node->class->parts);
                $constName = $node->name;
                return $className . '::' . $constName;
            default:
                throw new \LogicException('Unable to compile expression: ' . $type);
        }
    }
}
