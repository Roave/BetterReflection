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
        if ($node instanceof Node\Scalar) {
            return $this->compileScalar($node);
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

        throw new Exception\UnableToCompileNode('Unable to compile expression: ' . get_class($node));
    }

    /**
     * @param Node\Scalar\String_|Node\Scalar\DNumber|Node\Scalar\LNumber|Node\Scalar $node
     * @return string|int|float
     */
    private function compileScalar($node)
    {
        return $node->value;
    }

    /**
     * @param Node\Expr\Array_ $node
     * @return array
     */
    private function compileArray(Node\Expr\Array_ $node)
    {
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

    /**
     * @param Node\Expr\ConstFetch $node
     * @return bool|mixed|null
     */
    private function compileConstFetch(Node\Expr\ConstFetch $node)
    {
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

    /**
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
