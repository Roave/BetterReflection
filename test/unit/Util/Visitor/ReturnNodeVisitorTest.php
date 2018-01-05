<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Util\Visitor;

use PhpParser\Node;
use PhpParser\NodeTraverser;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\Util\Visitor\ReturnNodeVisitor;

/**
 * @covers \Roave\BetterReflection\Util\Visitor\ReturnNodeVisitor
 */
class ReturnNodeVisitorTest extends TestCase
{
    public function outOfScopeNodeTypeProvider() : array
    {
        return [
            'onlyExpectedNodesAdded' => [
                [
                    new Node\Scalar\MagicConst\File(),
                    new Node\Stmt\Return_(),
                ],
                1,
            ],
            'returnWithinClosureShouldNotBeReturned' => [
                [
                    new Node\Expr\Closure([
                        new Node\Stmt\Return_(),
                    ]),
                    new Node\Stmt\Return_(),
                ],
                1,
            ],
            'returnWithinAnonymousClassShouldNotBeReturned' => [
                [
                    new Node\Stmt\Class_('', [
                        new Node\Stmt\Return_(),
                    ]),
                    new Node\Stmt\Return_(),
                ],
                1,
            ],
        ];
    }

    /**
     * @param Node[] $statements
     * @dataProvider outOfScopeNodeTypeProvider
     */
    public function testOnlyExpectedReturnNodesAreReturned(array $statements, int $expectedReturns) : void
    {
        $visitor = new ReturnNodeVisitor();

        $traverser = new NodeTraverser();
        $traverser->addVisitor($visitor);

        $traverser->traverse($statements);

        $foundNodes = $visitor->getReturnNodes();
        self::assertCount($expectedReturns, $foundNodes);
        self::assertContainsOnlyInstancesOf(Node\Stmt\Return_::class, $foundNodes);
    }
}
