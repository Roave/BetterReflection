<?php

namespace BetterReflectionTest\Util\Visitor;

use BetterReflection\Util\Visitor\ReturnNodeVisitor;
use PhpParser\Node;

/**
 * @covers \BetterReflection\Util\Visitor\ReturnNodeVisitor
 */
class ReturnNodeVisitorTest extends \PHPUnit_Framework_TestCase
{
    public function testOnlyReturnNodesAreAdded()
    {
        $visitor = new ReturnNodeVisitor();

        $this->assertCount(0, $visitor->getReturnNodes());

        $visitor->enterNode(new Node\Scalar\MagicConst\File());

        $this->assertCount(0, $visitor->getReturnNodes());

        $visitor->enterNode(new Node\Stmt\Return_());

        $this->assertCount(1, $visitor->getReturnNodes());
        $this->assertContainsOnlyInstancesOf(Node\Stmt\Return_::class, $visitor->getReturnNodes());
    }

    public function outOfScopeNodeTypeProvider()
    {
        return [
            'closure' => [new Node\Expr\Closure()],
            'anonymousClass' => [new Node\Stmt\Class_('')],
        ];
    }

    /**
     * @param Node $nodeType
     * @dataProvider outOfScopeNodeTypeProvider
     */
    public function testReturnNodesWithinNodeTypesAreNotAdded(Node $nodeType)
    {
        $visitor = new ReturnNodeVisitor();

        $this->assertCount(0, $visitor->getReturnNodes());

        $visitor->enterNode($nodeType);
        $visitor->enterNode(new Node\Stmt\Return_());

        $this->assertCount(0, $visitor->getReturnNodes());

        $visitor->leaveNode($nodeType);

        $visitor->enterNode(new Node\Stmt\Return_());

        $this->assertCount(1, $visitor->getReturnNodes());
    }
}
