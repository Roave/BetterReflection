<?php

namespace BetterReflection\NodeCompiler;

use BetterReflection\Reflection\ReflectionClass;
use BetterReflection\Reflector\Reflector;
use PhpParser\Node;

class CompileNodeToValue
{
    /**
     * Compile an expression from a node into a value.
     *
     * @param Node $node
     * @param Reflector $reflector
     * @return mixed
     * @throw Exception\UnableToCompileNode
     */
    public function __invoke(Node $node, Reflector $reflector)
    {
        if ($node instanceof Node\Scalar\String_
            || $node instanceof Node\Scalar\DNumber
            || $node instanceof Node\Scalar\LNumber) {
            return $node->value;
        }

        // common edge case - negative numbers
        if ($node instanceof Node\Expr\UnaryMinus) {
            return $this($node->expr, $reflector) * -1;
        }

        if ($node instanceof Node\Expr\Array_) {
            return $this->compileArray($node, $reflector);
        }

        if ($node instanceof Node\Expr\ConstFetch) {
            return $this->compileConstFetch($node);
        }

        if ($node instanceof Node\Expr\ClassConstFetch) {
            return $this->compileClassConstFetch($node, $reflector);
        }

        if ($node instanceof Node\Expr\BinaryOp) {
            return $this->compileBinaryOperator($node, $reflector);
        }

        throw new Exception\UnableToCompileNode('Unable to compile expression: ' . get_class($node));
    }

    /**
     * Compile arrays
     *
     * @param Node\Expr\Array_ $arrayNode
     * @param Reflector $reflector
     * @return array
     */
    private function compileArray(Node\Expr\Array_ $arrayNode, Reflector $reflector)
    {
        $compiledArray = [];
        foreach ($arrayNode->items as $arrayItem) {
            $compiledValue = $this($arrayItem->value, $reflector);

            if (null == $arrayItem->key) {
                $compiledArray[] = $compiledValue;
                continue;
            }

            $compiledArray[$this($arrayItem->key, $reflector)] = $compiledValue;
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
     * @param Reflector $reflector
     * @return string
     */
    private function compileClassConstFetch(Node\Expr\ClassConstFetch $node, Reflector $reflector)
    {
        $className = implode('\\', $node->class->parts);

        /* @var ReflectionClass $classInfo */
        $classInfo = $reflector->reflect($className);

        $constName = $node->name;
        return $classInfo->getConstant($constName);
    }

    /**
     * Compile a binary operator node
     *
     * @param Node\Expr\BinaryOp $node
     * @param Reflector $reflector
     * @return mixed
     */
    private function compileBinaryOperator(Node\Expr\BinaryOp $node, Reflector $reflector)
    {
        if ($node instanceof Node\Expr\BinaryOp\Plus) {
            return $this($node->left, $reflector) + $this($node->right, $reflector);
        }

        if ($node instanceof Node\Expr\BinaryOp\Mul) {
            return $this($node->left, $reflector) * $this($node->right, $reflector);
        }

        if ($node instanceof Node\Expr\BinaryOp\Minus) {
            return $this($node->left, $reflector) - $this($node->right, $reflector);
        }

        if ($node instanceof Node\Expr\BinaryOp\Div) {
            return $this($node->left, $reflector) / $this($node->right, $reflector);
        }

        if ($node instanceof Node\Expr\BinaryOp\Concat) {
            return $this($node->left, $reflector) . $this($node->right, $reflector);
        }

        if ($node instanceof Node\Expr\BinaryOp\BooleanAnd) {
            return $this($node->left, $reflector) && $this($node->right, $reflector);
        }

        if ($node instanceof Node\Expr\BinaryOp\BooleanOr) {
            return $this($node->left, $reflector) || $this($node->right, $reflector);
        }

        if ($node instanceof Node\Expr\BinaryOp\BitwiseAnd) {
            return $this($node->left, $reflector) & $this($node->right, $reflector);
        }

        if ($node instanceof Node\Expr\BinaryOp\BitwiseOr) {
            return $this($node->left, $reflector) | $this($node->right, $reflector);
        }

        if ($node instanceof Node\Expr\BinaryOp\BitwiseXor) {
            return $this($node->left, $reflector) ^ $this($node->right, $reflector);
        }

        if ($node instanceof Node\Expr\BinaryOp\Equal) {
            return $this($node->left, $reflector) == $this($node->right, $reflector);
        }

        if ($node instanceof Node\Expr\BinaryOp\Greater) {
            return $this($node->left, $reflector) > $this($node->right, $reflector);
        }

        if ($node instanceof Node\Expr\BinaryOp\GreaterOrEqual) {
            return $this($node->left, $reflector) >= $this($node->right, $reflector);
        }

        if ($node instanceof Node\Expr\BinaryOp\Identical) {
            return $this($node->left, $reflector) === $this($node->right, $reflector);
        }

        if ($node instanceof Node\Expr\BinaryOp\LogicalAnd) {
            return $this($node->left, $reflector) and $this($node->right, $reflector);
        }

        if ($node instanceof Node\Expr\BinaryOp\LogicalOr) {
            return $this($node->left, $reflector) or $this($node->right, $reflector);
        }

        if ($node instanceof Node\Expr\BinaryOp\LogicalXor) {
            return $this($node->left, $reflector) xor $this($node->right, $reflector);
        }

        if ($node instanceof Node\Expr\BinaryOp\Mod) {
            return $this($node->left, $reflector) % $this($node->right, $reflector);
        }

        if ($node instanceof Node\Expr\BinaryOp\NotEqual) {
            return $this($node->left, $reflector) != $this($node->right, $reflector);
        }

        if ($node instanceof Node\Expr\BinaryOp\NotIdentical) {
            return $this($node->left, $reflector) !== $this($node->right, $reflector);
        }

        if ($node instanceof Node\Expr\BinaryOp\Pow) {
            return $this($node->left, $reflector) ** $this($node->right, $reflector);
        }

        if ($node instanceof Node\Expr\BinaryOp\ShiftLeft) {
            return $this($node->left, $reflector) << $this($node->right, $reflector);
        }

        if ($node instanceof Node\Expr\BinaryOp\ShiftRight) {
            return $this($node->left, $reflector) >> $this($node->right, $reflector);
        }

        if ($node instanceof Node\Expr\BinaryOp\Smaller) {
            return $this($node->left, $reflector) < $this($node->right, $reflector);
        }

        if ($node instanceof Node\Expr\BinaryOp\SmallerOrEqual) {
            return $this($node->left, $reflector) <= $this($node->right, $reflector);
        }

        throw new Exception\UnableToCompileNode('Unable to compile binary operator: ' . get_class($node));
    }
}
