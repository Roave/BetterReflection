<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Util\Visitor;

use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;

class MethodCallNodeVisitor extends NodeVisitorAbstract
{
    /** @var Node\Expr\MethodCall[] */
    private $methodCallNodes = [];

    public function enterNode(Node $node) : ?int
    {
        if ($this->isScopeChangingNode($node)) {
            return NodeTraverser::DONT_TRAVERSE_CHILDREN;
        }

        if ($node instanceof Node\Expr\MethodCall) {
            $this->methodCallNodes[] = $node;
        }

        return null;
    }

    private function isScopeChangingNode(Node $node) : bool
    {
        return $node instanceof Node\FunctionLike || $node instanceof Node\Stmt\Class_;
    }

    /**
     * @return Node\Expr\MethodCall[]
     */
    public function getMethodCallNodes() : array
    {
        return $this->methodCallNodes;
    }
}
