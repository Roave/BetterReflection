<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Util;

use PhpParser\Node;
use Roave\BetterReflection\Reflection\Exception\InvalidConstantNode;

use function count;
use function in_array;

/**
 * @internal
 */
final class ConstantNodeChecker
{
    /**
     * @throws InvalidConstantNode
     */
    public static function assertValidDefineFunctionCall(Node\Expr\FuncCall $node): void
    {
        if (! ($node->name instanceof Node\Name)) {
            throw InvalidConstantNode::create($node);
        }

        if ($node->name->toLowerString() !== 'define') {
            throw InvalidConstantNode::create($node);
        }

        if (! in_array(count($node->args), [2, 3], true)) {
            throw InvalidConstantNode::create($node);
        }

        if (! ($node->args[0]->value instanceof Node\Scalar\String_)) {
            throw InvalidConstantNode::create($node);
        }

        $valueNode = $node->args[1]->value;

        if ($valueNode instanceof Node\Expr\FuncCall) {
            throw InvalidConstantNode::create($node);
        }

        if ($valueNode instanceof Node\Expr\Variable) {
            throw InvalidConstantNode::create($node);
        }
    }
}
