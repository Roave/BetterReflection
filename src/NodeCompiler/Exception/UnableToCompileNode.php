<?php

declare(strict_types=1);

namespace Roave\BetterReflection\NodeCompiler\Exception;

use LogicException;
use PhpParser\Node;
use Roave\BetterReflection\NodeCompiler\CompilerContext;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Util\FileHelper;

use function assert;
use function sprintf;

/** @internal */
class UnableToCompileNode extends LogicException
{
    private string|null $constantName = null;

    public function constantName(): string|null
    {
        return $this->constantName;
    }

    public static function forUnRecognizedExpressionInContext(Node\Expr $expression, CompilerContext $context): self
    {
        return new self(sprintf(
            'Unable to compile expression in %s: unrecognized node type %s in file %s (line %d)',
            self::compilerContextToContextDescription($context),
            $expression::class,
            self::getFileName($context),
            $expression->getLine(),
        ));
    }

    public static function becauseOfNotFoundClassConstantReference(
        CompilerContext $fetchContext,
        ReflectionClass $targetClass,
        Node\Expr\ClassConstFetch $constantFetch,
    ): self {
        assert($constantFetch->name instanceof Node\Identifier);

        return new self(sprintf(
            'Could not locate constant %s::%s while trying to evaluate constant expression in %s in file %s (line %d)',
            $targetClass->getName(),
            $constantFetch->name->name,
            self::compilerContextToContextDescription($fetchContext),
            self::getFileName($fetchContext),
            $constantFetch->getLine(),
        ));
    }

    public static function becauseOfNotFoundConstantReference(
        CompilerContext $fetchContext,
        Node\Expr\ConstFetch $constantFetch,
        string $constantName,
    ): self {
        $exception = new self(sprintf(
            'Could not locate constant "%s" while evaluating expression in %s in file %s (line %d)',
            $constantName,
            self::compilerContextToContextDescription($fetchContext),
            self::getFileName($fetchContext),
            $constantFetch->getLine(),
        ));

        $exception->constantName = $constantName;

        return $exception;
    }

    public static function becauseOfInvalidEnumCasePropertyFetch(
        CompilerContext $fetchContext,
        ReflectionClass $targetClass,
        Node\Expr\PropertyFetch $propertyFetch,
    ): self {
        assert($propertyFetch->var instanceof Node\Expr\ClassConstFetch);
        assert($propertyFetch->var->name instanceof Node\Identifier);
        assert($propertyFetch->name instanceof Node\Identifier);

        return new self(sprintf(
            'Could not get %s::%s->%s while trying to evaluate constant expression in %s in file %s (line %d)',
            $targetClass->getName(),
            $propertyFetch->var->name->name,
            $propertyFetch->name->toString(),
            self::compilerContextToContextDescription($fetchContext),
            self::getFileName($fetchContext),
            $propertyFetch->getLine(),
        ));
    }

    public static function becauseOfMissingFileName(
        CompilerContext $context,
        Node\Scalar\MagicConst\Dir|Node\Scalar\MagicConst\File $node,
    ): self {
        return new self(sprintf(
            'No file name for %s (line %d)',
            self::compilerContextToContextDescription($context),
            $node->getLine(),
        ));
    }

    public static function becauseOfInitializer(
        CompilerContext $context,
        Node\Expr\New_ $newNode,
    ): self {
        return new self(sprintf(
            'Unable to compile initializer in %s in file %s (line %d)',
            self::compilerContextToContextDescription($context),
            self::getFileName($context),
            $newNode->getLine(),
        ));
    }

    private static function getFileName(CompilerContext $fetchContext): string
    {
        $fileName = $fetchContext->getFileName();

        return $fileName !== null ? FileHelper::normalizeWindowsPath($fileName) : '""';
    }

    private static function compilerContextToContextDescription(CompilerContext $fetchContext): string
    {
        $class    = $fetchContext->getClass();
        $function = $fetchContext->getFunction();

        if ($class !== null && $function !== null) {
            return sprintf('method %s::%s()', $class->getName(), $function->getName());
        }

        if ($class !== null) {
            return sprintf('class %s', $class->getName());
        }

        if ($function !== null) {
            return sprintf('function %s()', $function->getName());
        }

        $namespace = $fetchContext->getNamespace();
        if ($namespace !== '') {
            return sprintf('namespace %s', $namespace);
        }

        return 'global namespace';
    }
}
