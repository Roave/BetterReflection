<?php

declare(strict_types=1);

namespace Roave\BetterReflection\NodeCompiler\Exception;

use LogicException;
use PhpParser\Node;
use Roave\BetterReflection\NodeCompiler\CompilerContext;
use Roave\BetterReflection\Reflection\ReflectionClass;
use function assert;
use function get_class;
use function reset;
use function sprintf;

class UnableToCompileNode extends LogicException
{
    /** @var string|null */
    private $constantName;

    public function constantName() : ?string
    {
        return $this->constantName;
    }

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
        assert($constantFetch->name instanceof Node\Identifier);

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
        $constantName = reset($constantFetch->name->parts);

        $exception = new self(sprintf(
            'Could not locate constant "%s" while evaluating expression in %s at line %s',
            $constantName,
            self::compilerContextToContextDescription($fetchContext),
            $constantFetch->getLine()
        ));

        $exception->constantName = $constantName;

        return $exception;
    }

    private static function compilerContextToContextDescription(CompilerContext $fetchContext) : string
    {
        // @todo improve in https://github.com/Roave/BetterReflection/issues/434
        return $fetchContext->hasSelf()
            ? $fetchContext->getSelf()->getName()
            : 'unknown context (probably a function)';
    }
}
