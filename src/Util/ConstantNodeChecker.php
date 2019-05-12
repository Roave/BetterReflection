<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Util;

use PhpParser\Node;
use function count;
use function in_array;

/**
 * @internal
 */
final class ConstantNodeChecker
{
    public static function isValidDefineFunctionCall(Node\Expr\FuncCall $node) : bool
    {
        if (! ($node->name instanceof Node\Name)) {
            return false;
        }

        if ($node->name->toLowerString() !== 'define') {
            return false;
        }

        if (! in_array(count($node->args), [2, 3], true)) {
            return false;
        }

        if (! ($node->args[0]->value instanceof Node\Scalar\String_)) {
            return false;
        }

        $valueNode = $node->args[1]->value;

        return ! ($valueNode instanceof Node\Expr\FuncCall)
            && ! ($valueNode instanceof Node\Expr\Variable);
    }
}
