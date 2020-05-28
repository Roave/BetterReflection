<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\TypesFinder\PhpDocumentor;

use phpDocumentor\Reflection\Types\Context;
use PhpParser\Builder\Use_;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\GroupUse;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Use_ as UseStatement;
use PhpParser\Node\Stmt\UseUse;
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
        $namespaceWithImports = new Namespace_(
            new Name('With\\Imports'),
            [
                (new Use_('ClassName', UseStatement::TYPE_NORMAL))->getNode(),
                (new Use_('ConstantName', UseStatement::TYPE_CONSTANT))->getNode(),
                (new Use_('FunctionName', UseStatement::TYPE_FUNCTION))->getNode(),
                (new Use_('UnknownName', UseStatement::TYPE_UNKNOWN))->getNode(),
                (new Use_('AAA\\BBB', UseStatement::TYPE_NORMAL))->getNode(),
                (new Use_('BBB\\CCC\\DDD', UseStatement::TYPE_NORMAL))->getNode(),
                (new Use_('Foo\\EEE\\FFF', UseStatement::TYPE_NORMAL))->getNode(),
                (new Use_('Foo', UseStatement::TYPE_NORMAL))->getNode(),
                (new Use_('GGG', UseStatement::TYPE_NORMAL))->as('HHH')->getNode(),
                (new Use_('III', UseStatement::TYPE_NORMAL))->as('JJJ')->getNode(),
                new GroupUse(
                    new Name('LLL'),
                    [
                        new UseUse(new Name('MMM')),
                        new UseUse(new Name('NNN'), 'OOO'),
                    ],
                ),
                (new Use_('\\PPP', UseStatement::TYPE_NORMAL))->getNode(),
                new Class_('ClassNode'), // class node, should be ignored
            ],
        );

        return [
            'No namespace' => [
                null,
                new Context(''),
            ],
            'Empty namespace' => [
                new Namespace_(),
                new Context(''),
            ],
            'Actual namespace' => [
                new Namespace_(new Name('Foo\\Bar')),
                new Context('Foo\\Bar'),
            ],
            'Actual namespace prefixed with \\' => [
                new Namespace_(new Name('\\Foo\\Bar')),
                new Context('Foo\\Bar'),
            ],
            'Complex use statement (with fucked up group statements - yes, I do hate those)' => [
                $namespaceWithImports,
                new Context(
                    'With\\Imports',
                    [
                        'ClassName' => 'ClassName',
                        'UnknownName' => 'UnknownName',
                        'BBB' => 'AAA\\BBB',
                        'DDD' => 'BBB\\CCC\\DDD',
                        'FFF' => 'Foo\\EEE\\FFF',
                        'Foo' => 'Foo',
                        'HHH' => 'GGG',
                        'JJJ' => 'III',
                        'MMM' => 'LLL\\MMM',
                        'OOO' => 'LLL\\NNN',
                        'PPP' => 'PPP',
                    ],
                ),
            ],
        ];
    }
}
