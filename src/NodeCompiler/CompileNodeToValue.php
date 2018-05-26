<?php

declare(strict_types=1);

namespace Roave\BetterReflection\NodeCompiler;

use PhpParser\ConstExprEvaluator;
use PhpParser\Node;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionClassConstant;
use Roave\BetterReflection\Reflector\Exception\IdentifierNotFound;
use Roave\BetterReflection\Util\FileHelper;
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
        if ($node instanceof Node\Stmt\Expression) {
            return $this($node->expr, $context);
        }

        $constExprEvaluator = new ConstExprEvaluator(function (Node\Expr $node) use ($context) {
            if ($node instanceof Node\Expr\ConstFetch) {
                return $this->compileConstFetch($node);
            }

            if ($node instanceof Node\Expr\ClassConstFetch) {
                return $this->compileClassConstFetch($node, $context);
            }

            if ($node instanceof Node\Scalar\MagicConst\Dir) {
                return $this->compileDirConstant($context);
            }

            if ($node instanceof Node\Scalar\MagicConst\Class_) {
                return $this->compileClassConstant($context);
            }

            throw new Exception\UnableToCompileNode('Unable to compile expression: ' . get_class($node));
        });

        return $constExprEvaluator->evaluateDirectly($node);
    }

    /**
     * Compile constant expressions
     *
     * @return bool|mixed|null
     * @throws UnableToCompileNode
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
     * @throws IdentifierNotFound
     */
    private function compileClassConstFetch(Node\Expr\ClassConstFetch $node, CompilerContext $context)
    {
        /** @var Node\Identifier $node->name */
        $nodeName = $node->name->name;
        /** @var Node\Name $node->class */
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

        /** @var ReflectionClassConstant $reflectionConstant */
        $reflectionConstant = $classInfo->getReflectionConstant($nodeName);

        return $this->__invoke(
            $reflectionConstant->getAst()->consts[$reflectionConstant->getPositionInAst()]->value,
            new CompilerContext($context->getReflector(), $classInfo)
        );
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
}
