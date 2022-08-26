<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Util;

use PhpParser\Node;
use Roave\BetterReflection\Reflection\Exception\InvalidConstantNode;

use function count;
use function in_array;

/** @internal */
final class ConstantNodeChecker
{
    private const DEFINE_ARGUMENTS_COUNTS = [2, 3];

    /** @throws InvalidConstantNode */
    public static function assertValidDefineFunctionCall(Node\Expr\FuncCall $node): void
    {
        if (! ($node->name instanceof Node\Name)) {
            throw InvalidConstantNode::create($node);
        }

        if ($node->name->toLowerString() !== 'define') {
            throw InvalidConstantNode::create($node);
        }

        if (! in_array(count($node->args), self::DEFINE_ARGUMENTS_COUNTS, true)) {
            throw InvalidConstantNode::create($node);
        }

        if (! ($node->args[0] instanceof Node\Arg) || ! ($node->args[0]->value instanceof Node\Scalar\String_)) {
            throw InvalidConstantNode::create($node);
        }

        if (! ($node->args[1] instanceof Node\Arg)) {
            throw InvalidConstantNode::create($node);
        }

        $valueNode = $node->args[1]->value;

        if ($valueNode instanceof Node\Expr\FuncCall && ! ($valueNode->name instanceof Node\Name && $valueNode->name->toLowerString() === 'constant')) {
            throw InvalidConstantNode::create($node);
        }

        if ($valueNode instanceof Node\Expr\Variable) {
            throw InvalidConstantNode::create($node);
        }
    }
}
