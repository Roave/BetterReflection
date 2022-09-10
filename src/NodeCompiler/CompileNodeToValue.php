<?php

declare(strict_types=1);

namespace Roave\BetterReflection\NodeCompiler;

use PhpParser\ConstExprEvaluator;
use PhpParser\Node;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionClassConstant;
use Roave\BetterReflection\Reflection\ReflectionEnum;
use Roave\BetterReflection\Reflector\Exception\IdentifierNotFound;
use Roave\BetterReflection\Util\FileHelper;

use function assert;
use function constant;
use function defined;
use function dirname;
use function explode;
use function in_array;
use function sprintf;

/** @internal */
class CompileNodeToValue
{
    private const TRUE_FALSE_NULL = ['true', 'false', 'null'];

    /**
     * Compile an expression from a node into a value.
     *
     * @param Node\Stmt\Expression|Node\Expr $node Node has to be processed by the PhpParser\NodeVisitor\NameResolver
     *
     * @throws Exception\UnableToCompileNode
     */
    public function __invoke(Node $node, CompilerContext $context): CompiledValue
    {
        if ($node instanceof Node\Stmt\Expression) {
            return $this($node->expr, $context);
        }

        $constantName = null;

        if (
            $node instanceof Node\Expr\ConstFetch
            && ! in_array($node->name->toLowerString(), self::TRUE_FALSE_NULL, true)
        ) {
            $constantName = $this->resolveConstantName($node, $context);
        } elseif ($node instanceof Node\Expr\ClassConstFetch) {
            $constantName = $this->resolveClassConstantName($node, $context);
        }

        $constExprEvaluator = new ConstExprEvaluator(function (Node\Expr $node) use ($context, $constantName): mixed {
            if ($node instanceof Node\Expr\ConstFetch) {
                return $this->getConstantValue($node, $constantName, $context);
            }

            if ($node instanceof Node\Expr\ClassConstFetch) {
                return $this->getClassConstantValue($node, $constantName, $context);
            }

            if ($node instanceof Node\Expr\New_) {
                throw Exception\UnableToCompileNode::becauseOfInitializer($context, $node);
            }

            if ($node instanceof Node\Scalar\MagicConst\Dir) {
                return $this->compileDirConstant($context, $node);
            }

            if ($node instanceof Node\Scalar\MagicConst\File) {
                return $this->compileFileConstant($context, $node);
            }

            if ($node instanceof Node\Scalar\MagicConst\Class_) {
                return $this->compileClassConstant($context);
            }

            if ($node instanceof Node\Scalar\MagicConst\Line) {
                return $node->getLine();
            }

            if ($node instanceof Node\Scalar\MagicConst\Namespace_) {
                return $context->getNamespace();
            }

            if ($node instanceof Node\Scalar\MagicConst\Method) {
                $class    = $context->getClass();
                $function = $context->getFunction();

                if ($class !== null && $function !== null) {
                    return sprintf('%s::%s', $class->getName(), $function->getName());
                }

                if ($function !== null) {
                    return $function->getName();
                }

                return '';
            }

            if ($node instanceof Node\Scalar\MagicConst\Function_) {
                return $context->getFunction()?->getName() ?? '';
            }

            if ($node instanceof Node\Scalar\MagicConst\Trait_) {
                $class = $context->getClass();

                if ($class !== null && $class->isTrait()) {
                    return $class->getName();
                }

                return '';
            }

            if (
                $node instanceof Node\Expr\FuncCall
                && $node->name instanceof Node\Name
                && $node->name->toLowerString() === 'constant'
                && $node->args[0] instanceof Node\Arg
                && $node->args[0]->value instanceof Node\Scalar\String_
                && defined($node->args[0]->value->value)
            ) {
                return constant($node->args[0]->value->value);
            }

            if (
                $node instanceof Node\Expr\PropertyFetch
                && $node->var instanceof Node\Expr\ClassConstFetch
            ) {
                return $this->getEnumPropertyValue($node, $context);
            }

            throw Exception\UnableToCompileNode::forUnRecognizedExpressionInContext($node, $context);
        });

        /** @psalm-var mixed $value */
        $value = $constExprEvaluator->evaluateDirectly($node);

        return new CompiledValue($value, $constantName);
    }

    private function getEnumPropertyValue(Node\Expr\PropertyFetch $node, CompilerContext $context): mixed
    {
        assert($node->var instanceof Node\Expr\ClassConstFetch);
        assert($node->var->class instanceof Node\Name);

        $className = $this->resolveClassName($node->var->class->toString(), $context);
        $class     = $context->getReflector()->reflectClass($className);

        if (! $class instanceof ReflectionEnum) {
            throw Exception\UnableToCompileNode::becauseOfInvalidEnumCasePropertyFetch($context, $class, $node);
        }

        assert($node->var->name instanceof Node\Identifier);

        $case = $class->getCase($node->var->name->name);

        if ($case === null) {
            throw Exception\UnableToCompileNode::becauseOfInvalidEnumCasePropertyFetch($context, $class, $node);
        }

        assert($node->name instanceof Node\Identifier);

        return match ($node->name->toString()) {
            'value' => $case->getValue(),
            'name' => $case->getName(),
            default => throw Exception\UnableToCompileNode::becauseOfInvalidEnumCasePropertyFetch($context, $class, $node),
        };
    }

    private function resolveConstantName(Node\Expr\ConstFetch $constNode, CompilerContext $context): string
    {
        $constantName = $constNode->name->toString();
        $namespace    = $context->getNamespace();

        if ($constNode->name->isUnqualified()) {
            $namespacedConstantName = sprintf('%s\\%s', $namespace, $constantName);

            if ($this->constantExists($namespacedConstantName, $context)) {
                return $namespacedConstantName;
            }
        }

        if ($this->constantExists($constantName, $context)) {
            return $constantName;
        }

        throw Exception\UnableToCompileNode::becauseOfNotFoundConstantReference($context, $constNode, $constantName);
    }

    private function constantExists(string $constantName, CompilerContext $context): bool
    {
        if (defined($constantName)) {
            return true;
        }

        try {
            $context->getReflector()->reflectConstant($constantName);

            return true;
        } catch (IdentifierNotFound) {
            return false;
        }
    }

    private function getConstantValue(Node\Expr\ConstFetch $node, string|null $constantName, CompilerContext $context): mixed
    {
        // It's not resolved when constant value is expression
        $constantName ??= $this->resolveConstantName($node, $context);

        if (defined($constantName)) {
            return constant($constantName);
        }

        return $context->getReflector()->reflectConstant($constantName)->getValue();
    }

    private function resolveClassConstantName(Node\Expr\ClassConstFetch $node, CompilerContext $context): string
    {
        assert($node->name instanceof Node\Identifier);
        $constantName = $node->name->name;
        assert($node->class instanceof Node\Name);
        $className = $node->class->toString();

        return sprintf('%s::%s', $this->resolveClassName($className, $context), $constantName);
    }

    private function getClassConstantValue(Node\Expr\ClassConstFetch $node, string|null $classConstantName, CompilerContext $context): mixed
    {
        // It's not resolved when constant value is expression
        $classConstantName ??= $this->resolveClassConstantName($node, $context);

        [$className, $constantName] = explode('::', $classConstantName);

        if ($constantName === 'class') {
            return $className;
        }

        $classContext    = $context->getClass();
        $classReflection = $classContext !== null && $classContext->getName() === $className ? $classContext : $context->getReflector()->reflectClass($className);

        $reflectionConstant = $classReflection->getReflectionConstant($constantName);

        if (! $reflectionConstant instanceof ReflectionClassConstant) {
            throw Exception\UnableToCompileNode::becauseOfNotFoundClassConstantReference($context, $classReflection, $node);
        }

        return $reflectionConstant->getValue();
    }

    /**
     * Compile a __DIR__ node
     */
    private function compileDirConstant(CompilerContext $context, Node\Scalar\MagicConst\Dir $node): string
    {
        $fileName = $context->getFileName();

        if ($fileName === null) {
            throw Exception\UnableToCompileNode::becauseOfMissingFileName($context, $node);
        }

        return dirname(FileHelper::normalizeWindowsPath($fileName));
    }

    /**
     * Compile a __FILE__ node
     */
    private function compileFileConstant(CompilerContext $context, Node\Scalar\MagicConst\File $node): string
    {
        $fileName = $context->getFileName();

        if ($fileName === null) {
            throw Exception\UnableToCompileNode::becauseOfMissingFileName($context, $node);
        }

        return FileHelper::normalizeWindowsPath($fileName);
    }

    /**
     * Compiles magic constant __CLASS__
     */
    private function compileClassConstant(CompilerContext $context): string
    {
        return $context->getClass()?->getName() ?? '';
    }

    private function resolveClassName(string $className, CompilerContext $context): string
    {
        if ($className !== 'self' && $className !== 'static' && $className !== 'parent') {
            return $className;
        }

        $classContext = $context->getClass();
        assert($classContext !== null);

        if ($className !== 'parent') {
            return $classContext->getName();
        }

        $parentClass = $classContext->getParentClass();
        assert($parentClass instanceof ReflectionClass);

        return $parentClass->getName();
    }
}
