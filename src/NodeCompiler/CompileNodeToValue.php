<?php

declare(strict_types=1);

namespace Roave\BetterReflection\NodeCompiler;

use PhpParser\Node;
use ReflectionFunction;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Util\FileHelper;
use function array_combine;
use function array_map;
use function constant;
use function defined;
use function dirname;
use function get_class;
use function realpath;
use function reset;
use function sprintf;

class CompileNodeToValue
{
    /**
     * @var callable[]|null indexed by supported expression node class name
     */
    private static $nodeEvaluators;

    /**
     * Compile an expression from a node into a value.
     *
     * @param Node $node Node has to be processed by the PhpParser\NodeVisitor\NameResolver
     *
     * @return mixed
     *
     * @throws Exception\UnableToCompileNode
     */
    public function __invoke(Node $node, CompilerContext $context)
    {
        if ($node instanceof Node\Scalar\String_
            || $node instanceof Node\Scalar\DNumber
            || $node instanceof Node\Scalar\LNumber) {
            return $node->value;
        }

        // common edge case - negative numbers
        if ($node instanceof Node\Expr\UnaryMinus) {
            return $this($node->expr, $context) * -1;
        }

        if ($node instanceof Node\Expr\Array_) {
            return $this->compileArray($node, $context);
        }

        if ($node instanceof Node\Expr\ConstFetch) {
            return $this->compileConstFetch($node);
        }

        if ($node instanceof Node\Expr\ClassConstFetch) {
            return $this->compileClassConstFetch($node, $context);
        }

        if ($node instanceof Node\Expr\BinaryOp) {
            return $this->compileBinaryOperator($node, $context);
        }

        if ($node instanceof Node\Scalar\MagicConst\Dir) {
            return $this->compileDirConstant($context);
        }

        if ($node instanceof Node\Scalar\MagicConst\Class_) {
            return $this->compileClassConstant($context);
        }

        throw new Exception\UnableToCompileNode('Unable to compile expression: ' . get_class($node));
    }

    /**
     * Compile arrays
     *
     * @return mixed[]
     */
    private function compileArray(Node\Expr\Array_ $arrayNode, CompilerContext $context) : array
    {
        $compiledArray = [];
        foreach ($arrayNode->items as $arrayItem) {
            $compiledValue = $this($arrayItem->value, $context);

            if ($arrayItem->key === null) {
                $compiledArray[] = $compiledValue;
                continue;
            }

            $compiledArray[$this($arrayItem->key, $context)] = $compiledValue;
        }
        return $compiledArray;
    }

    /**
     * Compile constant expressions
     *
     * @return bool|mixed|null
     * @throws \Roave\BetterReflection\NodeCompiler\Exception\UnableToCompileNode
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
                if (! defined($firstName)) {
                    throw new Exception\UnableToCompileNode(
                        sprintf('Constant "%s" has not been defined', $firstName)
                    );
                }

                return constant($firstName);
        }
    }

    /**
     * Compile class constants
     *
     * @return string|int|float|bool|mixed[]|null
     * @throws \Roave\BetterReflection\Reflector\Exception\IdentifierNotFound
     */
    private function compileClassConstFetch(Node\Expr\ClassConstFetch $node, CompilerContext $context)
    {
        /** @var string $nodeName */
        $nodeName  = $node->name->toString();
        $className = $node->class->toString();

        if ($nodeName === 'class') {
            return $className;
        }

        /** @var ReflectionClass|null $classInfo */
        $classInfo = null;

        if ($className === 'self' || $className === 'static') {
            $classInfo = $this->getConstantDeclaringClass($nodeName, $context->getSelf());
        }

        if ($classInfo === null) {
            /** @var ReflectionClass $classInfo */
            $classInfo = $context->getReflector()->reflect($className);
        }

        $reflectionConstant = $classInfo->getReflectionConstant($nodeName);

        return $this->__invoke(
            $reflectionConstant->getAst()->consts[$reflectionConstant->getPositionInAst()]->value,
            new CompilerContext($context->getReflector(), $classInfo)
        );
    }

    /**
     * Compile a binary operator node
     *
     *
     * @return mixed
     *
     * @throws \Roave\BetterReflection\NodeCompiler\Exception\UnableToCompileNode
     */
    private function compileBinaryOperator(Node\Expr\BinaryOp $node, CompilerContext $context)
    {
        $evaluators = self::loadEvaluators();
        $nodeClass  = get_class($node);

        if (! isset($evaluators[$nodeClass])) {
            throw new Exception\UnableToCompileNode(sprintf(
                'Unable to compile binary operator: %s',
                $nodeClass
            ));
        }

        // Welcome to method overloading implemented PHP-style. Yay?
        return $evaluators[$nodeClass]($node, $context, $this);
    }

    /**
     * Compile a __DIR__ node
     */
    private function compileDirConstant(CompilerContext $context) : string
    {
        return FileHelper::normalizeWindowsPath(dirname(realpath($context->getFileName())));
    }

    /**
     * Compiles magic constant __CLASS__
     */
    private function compileClassConstant(CompilerContext $context) : string
    {
        return $context->hasSelf() ? $context->getSelf()->getName() : '';
    }

    private function getConstantDeclaringClass(string $constantName, ReflectionClass $class) : ?ReflectionClass
    {
        if ($class->hasConstant($constantName)) {
            return $class;
        }

        $parentClass = $class->getParentClass();

        return $parentClass ? $this->getConstantDeclaringClass($constantName, $parentClass) : null;
    }

    /**
     * @return callable[] indexed by node class name
     */
    private static function loadEvaluators() : array
    {
        if (self::$nodeEvaluators) {
            return self::$nodeEvaluators;
        }

        $evaluators = self::makeEvaluators();

        return self::$nodeEvaluators = array_combine(
            array_map(function (callable $nodeEvaluator) : string {
                /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
                /** @noinspection NullPointerExceptionInspection */
                return (new ReflectionFunction($nodeEvaluator))->getParameters()[0]->getType()->getName();
            }, $evaluators),
            $evaluators
        );
    }

    /**
     * @return callable[]
     */
    private static function makeEvaluators() : array
    {
        return [
            function (Node\Expr\BinaryOp\Plus $node, CompilerContext $context, self $next) {
                return $next($node->left, $context) + $next($node->right, $context);
            },
            function (Node\Expr\BinaryOp\Mul $node, CompilerContext $context, self $next) {
                return $next($node->left, $context) * $next($node->right, $context);
            },
            function (Node\Expr\BinaryOp\Minus $node, CompilerContext $context, self $next) {
                return $next($node->left, $context) - $next($node->right, $context);
            },
            function (Node\Expr\BinaryOp\Div $node, CompilerContext $context, self $next) {
                return $next($node->left, $context) / $next($node->right, $context);
            },
            function (Node\Expr\BinaryOp\Concat $node, CompilerContext $context, self $next) {
                return $next($node->left, $context) . $next($node->right, $context);
            },
            function (Node\Expr\BinaryOp\BooleanAnd $node, CompilerContext $context, self $next) {
                return $next($node->left, $context) && $next($node->right, $context);
            },
            function (Node\Expr\BinaryOp\BooleanOr $node, CompilerContext $context, self $next) {
                return $next($node->left, $context) || $next($node->right, $context);
            },
            function (Node\Expr\BinaryOp\BitwiseAnd $node, CompilerContext $context, self $next) {
                return $next($node->left, $context) & $next($node->right, $context);
            },
            function (Node\Expr\BinaryOp\BitwiseOr $node, CompilerContext $context, self $next) {
                return $next($node->left, $context) | $next($node->right, $context);
            },
            function (Node\Expr\BinaryOp\BitwiseXor $node, CompilerContext $context, self $next) {
                return $next($node->left, $context) ^ $next($node->right, $context);
            },
            function (Node\Expr\BinaryOp\Equal $node, CompilerContext $context, self $next) {
                /** @noinspection TypeUnsafeComparisonInspection */
                // phpcs:disable SlevomatCodingStandard.ControlStructures.DisallowEqualOperators
                return $next($node->left, $context) == $next($node->right, $context);
                // phpcs:enable
            },
            function (Node\Expr\BinaryOp\Greater $node, CompilerContext $context, self $next) {
                return $next($node->left, $context) > $next($node->right, $context);
            },
            function (Node\Expr\BinaryOp\GreaterOrEqual $node, CompilerContext $context, self $next) {
                return $next($node->left, $context) >= $next($node->right, $context);
            },
            function (Node\Expr\BinaryOp\Identical $node, CompilerContext $context, self $next) {
                return $next($node->left, $context) === $next($node->right, $context);
            },
            function (Node\Expr\BinaryOp\LogicalAnd $node, CompilerContext $context, self $next) {
                // phpcs:disable Squiz.Operators.ValidLogicalOperators.NotAllowed
                return $next($node->left, $context) and $next($node->right, $context);
                // phpcs:enable
            },
            function (Node\Expr\BinaryOp\LogicalOr $node, CompilerContext $context, self $next) {
                // phpcs:disable Squiz.Operators.ValidLogicalOperators.NotAllowed
                return $next($node->left, $context) or $next($node->right, $context);
                // phpcs:enable
            },
            function (Node\Expr\BinaryOp\LogicalXor $node, CompilerContext $context, self $next) {
                return $next($node->left, $context) xor $next($node->right, $context);
            },
            function (Node\Expr\BinaryOp\Mod $node, CompilerContext $context, self $next) {
                return $next($node->left, $context) % $next($node->right, $context);
            },
            function (Node\Expr\BinaryOp\NotEqual $node, CompilerContext $context, self $next) {
                /** @noinspection TypeUnsafeComparisonInspection */
                // phpcs:disable SlevomatCodingStandard.ControlStructures.DisallowEqualOperators
                return $next($node->left, $context) != $next($node->right, $context);
                // phpcs:enable
            },
            function (Node\Expr\BinaryOp\NotIdentical $node, CompilerContext $context, self $next) {
                return $next($node->left, $context) !== $next($node->right, $context);
            },
            function (Node\Expr\BinaryOp\Pow $node, CompilerContext $context, self $next) {
                return $next($node->left, $context) ** $next($node->right, $context);
            },
            function (Node\Expr\BinaryOp\ShiftLeft $node, CompilerContext $context, self $next) {
                return $next($node->left, $context) << $next($node->right, $context);
            },
            function (Node\Expr\BinaryOp\ShiftRight $node, CompilerContext $context, self $next) {
                return $next($node->left, $context) >> $next($node->right, $context);
            },
            function (Node\Expr\BinaryOp\Smaller $node, CompilerContext $context, self $next) {
                return $next($node->left, $context) < $next($node->right, $context);
            },
            function (Node\Expr\BinaryOp\SmallerOrEqual $node, CompilerContext $context, self $next) {
                return $next($node->left, $context) <= $next($node->right, $context);
            },
            function (Node\Expr\BinaryOp\Spaceship $node, CompilerContext $context, self $next) {
                return $next($node->left, $context) <=> $next($node->right, $context);
            },
        ];
    }
}
