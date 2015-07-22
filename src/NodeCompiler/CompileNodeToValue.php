<?php

namespace BetterReflection\NodeCompiler;

use PhpParser\Node;

class CompileNodeToValue
{
    /**
     * Compile an expression from a node into a value.
     *
     * @param Node $node
     * @return mixed
     * @throw Exception\UnableToCompileNode
     */
    public function __invoke(Node $node)
    {
        if ($node instanceof Node\Scalar\String_
            || $node instanceof Node\Scalar\DNumber
            || $node instanceof Node\Scalar\LNumber) {
            return $node->value;
        }

        // common edge case - negative numbers
        if ($node instanceof Node\Expr\UnaryMinus) {
            return $this->__invoke($node->expr) * -1;
        }

        if ($node instanceof Node\Expr\Array_) {
            $compiledArray = [];
            foreach ($node->items as $arrayItem) {
                $compiledValue = $this->__invoke($arrayItem->value);

                if (null == $arrayItem->key) {
                    $compiledArray[] = $compiledValue;
                    continue;
                }

                $compiledArray[$this->__invoke($arrayItem->key)] = $compiledValue;
            }
            return $compiledArray;
        }

        if ($node instanceof Node\Expr\ConstFetch) {
            $firstName = reset($node->name->parts);
            switch ($firstName) {
                case 'null':
                    return null;
                case 'false':
                    return false;
                case 'true':
                    return true;
                default:
                    // @todo this should evaluate the VALUE, not the name
                    /* @see https://github.com/Roave/BetterReflection/issues/19 */
                    return $firstName;
            }
        }

        if ($node instanceof Node\Expr\ClassConstFetch) {
            // @todo this should evaluate the VALUE, not the name
            /* @see https://github.com/Roave/BetterReflection/issues/19 */
            $className = implode('\\', $node->class->parts);
            $constName = $node->name;
            return $className . '::' . $constName;
        }

        throw new Exception\UnableToCompileNode('Unable to compile expression: ' . get_class($node));
    }
}
