<?php

namespace BetterReflection\NodeCompiler;

use PhpParser\Node;
use PhpParser\PrettyPrinter\Standard as Printer;

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
        if (!$this->isCompilable($node)) {
            throw new Exception\UnableToCompileNode('Unable to compile expression: ' . get_class($node));
        }

        if ($node instanceof Node\Expr\ConstFetch) {
            return $this->compileConstFetch($node);
        }

        if ($node instanceof Node\Expr\ClassConstFetch) {
            return $this->compileClassConstFetch($node);
        }

        $printer = new Printer();
        $code = $printer->prettyPrint([$node]);

        eval('$x = ' . $code);
        /* @var mixed $x */
        return $x;
    }

    private function isCompilable(Node $node)
    {
        if ($node instanceof Node\Expr\BinaryOp
            || $node instanceof Node\Scalar
            || $node instanceof Node\Expr\Array_
            || $node instanceof Node\Expr\ConstFetch
            || $node instanceof Node\Expr\ClassConstFetch) {
            /* @todo add more */
            return true;
        }

        return false;
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
