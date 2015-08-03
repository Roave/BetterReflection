<?php

namespace BetterReflection\NodeCompiler;

use BetterReflection\Reflector\Reflector;
use BetterReflection\Reflector\ClassReflector;
use BetterReflection\SourceLocator\DefaultSourceLocator;
use PhpParser\Node;
use PhpParser\PrettyPrinter\Standard as Printer;

class CompileNodeToValue
{
    private function createReflector()
    {
        return new ClassReflector(new DefaultSourceLocator);
    }

    /**
     * Compile an expression from a node into a value.
     *
     * @param Node $node
     * @return mixed
     * @throw Exception\UnableToCompileNode
     */
    public function __invoke(Node $node, Reflector $reflector = null)
    {
        if (!$this->isCompilable($node)) {
            throw new Exception\UnableToCompileNode('Unable to compile expression: ' . get_class($node));
        }

        //array_walk_recursive($node, [$this, 'whitelistConstFetch']);

//        if ($node instanceof Node\Expr\ClassConstFetch) {
//            return $this->compileClassConstFetch($node);
//        }

        $printer = new Printer();
        $code = $printer->prettyPrint([$node]);

        eval('$result = ' . $code);
        /* @var mixed $result */
        return $result;
    }

    private function isCompilable(Node $node)
    {
        if ($node instanceof Node\Expr\BinaryOp\Plus
            || $node instanceof Node\Expr\BinaryOp\Minus
            || $node instanceof Node\Expr\BinaryOp\Div
            || $node instanceof Node\Expr\BinaryOp\Mul
            || $node instanceof Node\Scalar\String_
            || $node instanceof Node\Scalar\DNumber
            || $node instanceof Node\Scalar\LNumber
            || $node instanceof Node\Expr\Array_
            || $node instanceof Node\Expr\UnaryMinus
            || $node instanceof Node\Expr\ConstFetch
            || $node instanceof Node\Expr\ClassConstFetch) {
            return true;
        }

        return false;
    }

    /**
     * @param Node\Expr\ConstFetch $node
     * @return bool|mixed|null
     */
    private function whitelistConstFetch(Node\Expr\ConstFetch $node)
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
                throw new Exception\UnableToCompileNode('Unable to compile constants');
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
