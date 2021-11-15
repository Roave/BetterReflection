<?php

declare(strict_types=1);

namespace Roave\BetterReflection\SourceLocator\SourceStubber\PhpStormStubs;

use PhpParser\BuilderFactory;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use Roave\BetterReflection\Reflection\Exception\InvalidConstantNode;
use Roave\BetterReflection\Util\ConstantNodeChecker;

use function array_key_exists;
use function assert;
use function constant;
use function defined;
use function in_array;
use function sprintf;
use function strtolower;
use function strtoupper;

/**
 * @internal
 */
class CachingVisitor extends NodeVisitorAbstract
{
    private const TRUE_FALSE_NULL = ['true', 'false', 'null'];

    /** @var array<string, Node\Stmt\ClassLike> */
    private array $classNodes = [];

    /** @var array<string, list<Node\Stmt\Function_>> */
    private array $functionNodes = [];

    /** @var array<string, Node\Stmt\Const_|Node\Expr\FuncCall> */
    private array $constantNodes = [];

    public function __construct(private BuilderFactory $builderFactory)
    {
    }

    public function enterNode(Node $node): ?int
    {
        if ($node instanceof Node\Stmt\ClassLike) {
            $nodeName                    = $node->namespacedName->toString();
            $this->classNodes[$nodeName] = $node;

            foreach ($node->getConstants() as $constantsNode) {
                foreach ($constantsNode->consts as $constNode) {
                    $constClassName = sprintf('%s::%s', $nodeName, $constNode->name->toString());
                    $this->updateConstantValue($constNode, $constClassName);
                }
            }

            // We need to traverse children to resolve attributes names for methods, properties etc.
            return null;
        }

        if (
            $node instanceof Node\Stmt\ClassMethod
            || $node instanceof Node\Stmt\Property
            || $node instanceof Node\Stmt\ClassConst
            || $node instanceof Node\Stmt\EnumCase
        ) {
            return NodeTraverser::DONT_TRAVERSE_CURRENT_AND_CHILDREN;
        }

        if ($node instanceof Node\Stmt\Function_) {
            $nodeName                         = $node->namespacedName->toString();
            $this->functionNodes[$nodeName][] = $node;

            return NodeTraverser::DONT_TRAVERSE_CHILDREN;
        }

        if ($node instanceof Node\Stmt\Const_) {
            foreach ($node->consts as $constNode) {
                $constNodeName = $constNode->namespacedName->toString();

                $this->updateConstantValue($constNode, $constNodeName);

                $this->constantNodes[$constNodeName] = $node;
            }

            return NodeTraverser::DONT_TRAVERSE_CHILDREN;
        }

        if ($node instanceof Node\Expr\FuncCall) {
            try {
                ConstantNodeChecker::assertValidDefineFunctionCall($node);
            } catch (InvalidConstantNode) {
                return null;
            }

            $argumentNameNode = $node->args[0];
            assert($argumentNameNode instanceof Node\Arg);
            $nameNode = $argumentNameNode->value;
            assert($nameNode instanceof Node\Scalar\String_);
            $constantName = $nameNode->value;

            if (in_array($constantName, self::TRUE_FALSE_NULL, true)) {
                $constantName    = strtoupper($constantName);
                $nameNode->value = $constantName;
            }

            $this->updateConstantValue($node, $constantName);

            $this->constantNodes[$constantName] = $node;

            if (
                array_key_exists(2, $node->args)
                && $node->args[2] instanceof Node\Arg
                && $node->args[2]->value instanceof Node\Expr\ConstFetch
                && $node->args[2]->value->name->toLowerString() === 'true'
            ) {
                $this->constantNodes[strtolower($constantName)] = $node;
            }

            return NodeTraverser::DONT_TRAVERSE_CHILDREN;
        }

        return null;
    }

    /**
     * @return array<string, Node\Stmt\ClassLike>
     */
    public function getClassNodes(): array
    {
        return $this->classNodes;
    }

    /**
     * @return array<string, list<Node\Stmt\Function_>>
     */
    public function getFunctionNodes(): array
    {
        return $this->functionNodes;
    }

    /**
     * @return array<string, Node\Stmt\Const_|Node\Expr\FuncCall>
     */
    public function getConstantNodes(): array
    {
        return $this->constantNodes;
    }

    public function clearNodes(): void
    {
        $this->classNodes    = [];
        $this->functionNodes = [];
        $this->constantNodes = [];
    }

    /**
     * Some constants has different values on different systems, some are not actual in stubs.
     */
    private function updateConstantValue(Node\Expr\FuncCall|Node\Const_ $node, string $constantName): void
    {
        if (! defined($constantName)) {
            return;
        }

        // @ because access to deprecated constant throws deprecated warning
        /** @var scalar|list<scalar>|null $constantValue */
        $constantValue           = @constant($constantName);
        $normalizedConstantValue = $this->builderFactory->val($constantValue);

        if ($node instanceof Node\Expr\FuncCall) {
            $argumentValueNode = $node->args[1];
            assert($argumentValueNode instanceof Node\Arg);
            $argumentValueNode->value = $normalizedConstantValue;
        } else {
            $node->value = $normalizedConstantValue;
        }
    }
}
