<?php
declare(strict_types=1);

namespace Roave\BetterReflectionTest\TypesFinder\PhpDocumentor;

use phpDocumentor\Reflection\Types\Context;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Namespace_;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\TypesFinder\PhpDocumentor\NamespaceNodeToReflectionTypeContext;

/**
 * @covers \Roave\BetterReflection\TypesFinder\PhpDocumentor\NamespaceNodeToReflectionTypeContext
 */
class NamespaceNodeToReflectionTypeContextTest extends TestCase
{
    /**
     * @dataProvider expectedContextsProvider
     */
    public function testConversion(?Namespace_ $namespace, Context $expectedContext) : void
    {
        self::assertEquals($expectedContext, (new NamespaceNodeToReflectionTypeContext())->__invoke($namespace));
    }

    public function expectedContextsProvider() : array
    {
        return [
            'No namespace' => [
                null,
                new Context(''),
            ],
            'Empty namespace' => [
                new Namespace_(new Name('')),
                new Context(''),
            ],
        ];
    }
}
