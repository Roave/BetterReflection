<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Util\Visitor;

use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;

class ReturnNodeVisitor extends NodeVisitorAbstract
{
    /** @var Node\Stmt\Return_[] */
    private $returnNodes = [];

    public function enterNode(Node $node) : ?int
    {
        if ($this->isScopeChangingNode($node)) {
            return NodeTraverser::DONT_TRAVERSE_CHILDREN;
        }

        if ($node instanceof Node\Stmt\Return_) {
            $this->returnNodes[] = $node;
        }

        return null;
    }

    private function isScopeChangingNode(Node $node) : bool
    {
        return $node instanceof Node\FunctionLike || $node instanceof Node\Stmt\Class_;
    }

    /**
     * @return Node\Stmt\Return_[]
     */
    public function getReturnNodes() : array
    {
        return $this->returnNodes;
    }
}
