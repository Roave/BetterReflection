<?php

declare(strict_types=1);

namespace Roave\BetterReflection\NodeCompiler\Exception;

use LogicException;
use PhpParser\Node;
use Roave\BetterReflection\NodeCompiler\CompilerContext;
use Roave\BetterReflection\Reflection\ReflectionClass;
use function get_class;
use function reset;
use function sprintf;

class UnableToCompileNode extends LogicException
{
    public static function forUnRecognizedExpressionInContext(Node\Expr $expression, CompilerContext $context) : self
    {
        return new self(sprintf(
            'Unable to compile expression in %s: unrecognized node type %s at line %d',
            self::compilerContextToContextDescription($context),
            get_class($expression),
            $expression->getLine()
        ));
    }

    public static function becauseOfNotFoundClassConstantReference(
        CompilerContext $fetchContext,
        ReflectionClass $targetClass,
        Node\Expr\ClassConstFetch $constantFetch
    ) : self {
        /** @var Node\Identifier $constantFetch->name */
        return new self(sprintf(
            'Could not locate constant %s::%s while trying to evaluate constant expression in %s at line %s',
            $targetClass->getName(),
            $constantFetch->name->name,
            self::compilerContextToContextDescription($fetchContext),
            $constantFetch->getLine()
        ));
    }

    public static function becauseOfNotFoundConstantReference(
        CompilerContext $fetchContext,
        Node\Expr\ConstFetch $constantFetch
    ) : self {
        /** @var Node\Name $constantFetch->name */
        return new self(sprintf(
            'Could not locate constant "%s" while evaluating expression in %s at line %s',
            reset($constantFetch->name->parts),
            self::compilerContextToContextDescription($fetchContext),
            $constantFetch->getLine()
        ));
    }

    private static function compilerContextToContextDescription(CompilerContext $fetchContext) : string
    {
        // @todo improve in https://github.com/Roave/BetterReflection/issues/434
        return $fetchContext->hasSelf()
            ? $fetchContext->getSelf()->getName()
            : 'unknown context (probably a function)';
    }
}
