<?php

namespace BetterReflection\Util\Visitor;

use PhpParser\NodeVisitorAbstract;
use PhpParser\Node;

class ReturnNodeVisitor extends NodeVisitorAbstract
{
    /**
     * @var Node\Stmt\Return_[]
     */
    private $returnNodes = [];

    private $scopeDepth = 1;

    public function enterNode(Node $node)
    {
        if ($this->isScopeChangingNode($node)) {
            $this->scopeDepth++;
        }

        if ($this->scopeDepth !== 1) {
            return;
        }

        if ($node instanceof Node\Stmt\Return_) {
            array_push($this->returnNodes, $node);
        }
    }

    public function leaveNode(Node $node)
    {
        if ($this->isScopeChangingNode($node)) {
            $this->scopeDepth--;
        }
    }

    private function isScopeChangingNode(Node $node)
    {
        return $node instanceof Node\Expr\Closure || $node instanceof Node\Stmt\Class_;
    }

    /**
     * @return Node\Stmt\Return_[]
     */
    public function getReturnNodes()
    {
        return $this->returnNodes;
    }
}
