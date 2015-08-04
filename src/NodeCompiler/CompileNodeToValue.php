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

        if ($node instanceof Node\Expr\BinaryOp) {
            return $this->compileBinaryOperator($node);
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

    private function compileBinaryOperator(Node\Expr\BinaryOp $node)
    {
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

        if ($node instanceof Node\Expr\BinaryOp\Concat) {
            return $this->__invoke($node->left) . $this->__invoke($node->right);
        }

        if ($node instanceof Node\Expr\BinaryOp\BooleanAnd) {
            return $this->__invoke($node->left) && $this->__invoke($node->right);
        }

        if ($node instanceof Node\Expr\BinaryOp\BooleanOr) {
            return $this->__invoke($node->left) || $this->__invoke($node->right);
        }

        if ($node instanceof Node\Expr\BinaryOp\BitwiseAnd) {
            return $this->__invoke($node->left) & $this->__invoke($node->right);
        }

        if ($node instanceof Node\Expr\BinaryOp\BitwiseOr) {
            return $this->__invoke($node->left) | $this->__invoke($node->right);
        }

        if ($node instanceof Node\Expr\BinaryOp\BitwiseXor) {
            return $this->__invoke($node->left) ^ $this->__invoke($node->right);
        }

        if ($node instanceof Node\Expr\BinaryOp\Equal) {
            return $this->__invoke($node->left) == $this->__invoke($node->right);
        }

        if ($node instanceof Node\Expr\BinaryOp\Greater) {
            return $this->__invoke($node->left) > $this->__invoke($node->right);
        }

        if ($node instanceof Node\Expr\BinaryOp\GreaterOrEqual) {
            return $this->__invoke($node->left) >= $this->__invoke($node->right);
        }

        if ($node instanceof Node\Expr\BinaryOp\Identical) {
            return $this->__invoke($node->left) === $this->__invoke($node->right);
        }

        if ($node instanceof Node\Expr\BinaryOp\LogicalAnd) {
            return $this->__invoke($node->left) and $this->__invoke($node->right);
        }

        if ($node instanceof Node\Expr\BinaryOp\LogicalOr) {
            return $this->__invoke($node->left) or $this->__invoke($node->right);
        }

        if ($node instanceof Node\Expr\BinaryOp\LogicalXor) {
            return $this->__invoke($node->left) xor $this->__invoke($node->right);
        }

        if ($node instanceof Node\Expr\BinaryOp\Mod) {
            return $this->__invoke($node->left) % $this->__invoke($node->right);
        }

        if ($node instanceof Node\Expr\BinaryOp\NotEqual) {
            return $this->__invoke($node->left) != $this->__invoke($node->right);
        }

        if ($node instanceof Node\Expr\BinaryOp\NotIdentical) {
            return $this->__invoke($node->left) !== $this->__invoke($node->right);
        }

        if ($node instanceof Node\Expr\BinaryOp\Pow) {
            return $this->__invoke($node->left) ** $this->__invoke($node->right);
        }

        if ($node instanceof Node\Expr\BinaryOp\ShiftLeft) {
            return $this->__invoke($node->left) << $this->__invoke($node->right);
        }

        if ($node instanceof Node\Expr\BinaryOp\ShiftRight) {
            return $this->__invoke($node->left) >> $this->__invoke($node->right);
        }

        if ($node instanceof Node\Expr\BinaryOp\Smaller) {
            return $this->__invoke($node->left) < $this->__invoke($node->right);
        }

        if ($node instanceof Node\Expr\BinaryOp\SmallerOrEqual) {
            return $this->__invoke($node->left) <= $this->__invoke($node->right);
        }

        throw new Exception\UnableToCompileNode('Unable to compile binary operator: ' . get_class($node));
    }
}
