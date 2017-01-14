<?php

namespace Roave\BetterReflectionTest\TypesFinder;

use Roave\BetterReflection\SourceLocator\Located\LocatedSource;
use Roave\BetterReflection\TypesFinder\FindTypeFromAst;
use PhpParser\Node\Name;
use phpDocumentor\Reflection\Types;
use phpDocumentor\Reflection\Types\ContextFactory;
use phpDocumentor\Reflection\Types\Context;

/**
 * @covers \Roave\BetterReflection\TypesFinder\FindTypeFromAst
 */
class FindTypeFromAstTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return array
     */
    public function findTypeFromAstTypeProvider()
    {
        return [
            ['int', Types\Integer::class, 'int'],
            ['string', Types\String_::class, 'string'],
            ['array', Types\Array_::class, 'array'],
            ['int[]', Types\Array_::class, 'int[]'],
            [new Name\FullyQualified('My\Awesome\Class'), Types\Object_::class, '\My\Awesome\Class'],
            [new Name('SomeClass'), Types\Object_::class, '\MyNamespace\SomeClass'],
            [new Name('Foo\Bar'), Types\Object_::class, '\MyNamespace\Foo\Bar'],
            ['callable', Types\Callable_::class, 'callable'],
        ];
    }

    /**
     * @param mixed $input
     * @param string $expected
     * @dataProvider findTypeFromAstTypeProvider
     */
    public function testFindTypeFromAst($input, $expected, $toStringValue)
    {
        $resolvedType = (new FindTypeFromAst())->__invoke(
            new Context('MyNamespace', []),
            $input
        );

        $this->assertInstanceOf($expected, $resolvedType);
        $this->assertSame($toStringValue, (string)$resolvedType);
    }

    public function testFindTypeFromAstReturnsNull()
    {
        $this->assertNull(
            (new FindTypeFromAst())->__invoke(
                new Context('MyNamespace', []),
                null
            )
        );
    }
}
