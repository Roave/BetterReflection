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

    public function enterNode(Node $node)
    {
        if ($node instanceof Node\Stmt\Return_) {
            array_push($this->returnNodes, $node);
        }
    }

    /**
     * @return Node\Stmt\Return_[]
     */
    public function getReturnNodes()
    {
        return $this->returnNodes;
    }
}
