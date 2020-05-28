<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Util\Visitor;

use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;

class FunctionCallNodeVisitor extends NodeVisitorAbstract
{
    /** @var Node\Expr\FuncCall[] */
    private $functionCallNodes = [];

    public function enterNode(Node $node) : ?int
    {
        if ($this->isScopeChangingNode($node)) {
            return NodeTraverser::DONT_TRAVERSE_CHILDREN;
        }

        if ($node instanceof Node\Expr\FuncCall) {
            $this->functionCallNodes[] = $node;
        }

        return null;
    }

    private function isScopeChangingNode(Node $node) : bool
    {
        return $node instanceof Node\FunctionLike || $node instanceof Node\Stmt\Class_;
    }

    /**
     * @return Node\Expr\FuncCall[]
     */
    public function getFunctionCallNodes() : array
    {
        return $this->functionCallNodes;
    }
}
