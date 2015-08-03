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
            return $this->compileArray($node);
        }

        if ($node instanceof Node\Expr\ConstFetch) {
            return $this->compileConstFetch($node);
        }

        if ($node instanceof Node\Expr\ClassConstFetch) {
            return $this->compileClassConstFetch($node);
        }

        if ($node instanceof Node\Expr\BinaryOp\Plus) {
            return $this->__invoke($node->left) + $this->__invoke($node->right);
        }

        if ($node instanceof Node\Expr\BinaryOp\Mul) {
            return $this->__invoke($node->left) * $this->__invoke($node->right);
        }

        if ($node instanceof Node\Expr\BinaryOp\Minus) {
            return $this->__invoke($node->left) - $this->__invoke($node->right);
        }

        if ($node instanceof Node\Expr\BinaryOp\Div) {
            return $this->__invoke($node->left) / $this->__invoke($node->right);
        }

        throw new Exception\UnableToCompileNode('Unable to compile expression: ' . get_class($node));
    }

    /**
     * Compile arrays
     *
     * @param Node\Expr\Array_ $arrayNode
     * @return array
     */
    private function compileArray(Node\Expr\Array_ $arrayNode)
    {
        $compiledArray = [];
        foreach ($arrayNode->items as $arrayItem) {
            $compiledValue = $this->__invoke($arrayItem->value);

            if (null == $arrayItem->key) {
                $compiledArray[] = $compiledValue;
                continue;
            }

            $compiledArray[$this->__invoke($arrayItem->key)] = $compiledValue;
        }
        return $compiledArray;
    }

    /**
     * Compile constant expressions
     *
     * @param Node\Expr\ConstFetch $constNode
     * @return bool|null
     */
    private function compileConstFetch(Node\Expr\ConstFetch $constNode)
    {
        $firstName = reset($constNode->name->parts);
        switch ($firstName) {
            case 'null':
                return null;
            case 'false':
                return false;
            case 'true':
                return true;
            default:
                throw new Exception\UnableToCompileNode('Unable to compile constant expressions');
        }
    }

    /**
     * Compile class constants
     *
     * @param Node\Expr\ClassConstFetch $node
     * @return string
     */
    private function compileClassConstFetch(Node\Expr\ClassConstFetch $node)
    {
        // @todo this should evaluate the VALUE, not the name
        /* @see https://github.com/Roave/BetterReflection/issues/19 */
        $className = implode('\\', $node->class->parts);
        $constName = $node->name;
        return $className . '::' . $constName;
    }
}
