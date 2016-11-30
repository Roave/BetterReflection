<?php

namespace Roave\BetterReflection\Util\Visitor;

use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PhpParser\Node;

class ReturnNodeVisitor extends NodeVisitorAbstract
{
    /**
     * @var Node\Stmt\Return_[]
     */
    private $returnNodes = [];

    public function enterNode(Node $node)
    {
        if ($this->isScopeChangingNode($node)) {
            return NodeTraverser::DONT_TRAVERSE_CHILDREN;
        }

        if ($node instanceof Node\Stmt\Return_) {
            array_push($this->returnNodes, $node);
        }
    }

    private function isScopeChangingNode(Node $node)
    {
        return $node instanceof Node\FunctionLike || $node instanceof Node\Stmt\Class_;
    }

    /**
     * @return Node\Stmt\Return_[]
     */
    public function getReturnNodes()
    {
        return $this->returnNodes;
    }
}
