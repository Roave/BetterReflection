<?php

namespace BetterReflectionTest\TypesFinder;

use BetterReflection\TypesFinder\FindTypeFromAst;
use PhpParser\Node\Name\FullyQualified;
use phpDocumentor\Reflection\Types;

/**
 * @covers \BetterReflection\TypesFinder\FindTypeFromAst
 */
class FindTypeFromAstTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return array
     */
    public function findTypeFromAstTypeProvider()
    {
        return [
            ['int', Types\Integer::class],
            [new FullyQualified('int'), Types\Integer::class],
            ['string', Types\String_::class],
            [new FullyQualified('string'), Types\String_::class],
            ['array', Types\Array_::class],
            [new FullyQualified('array'), Types\Array_::class],
            [new FullyQualified('My\Awesome\Class'), Types\Object_::class],
            [new FullyQualified('\My\Awesome\Class'), Types\Object_::class],
            [new FullyQualified('callable'), Types\Callable_::class],
        ];
    }

    /**
     * @param mixed $input
     * @param string $expected
     * @dataProvider findTypeFromAstTypeProvider
     */
    public function testFindTypeFromAst($input, $expected)
    {
        $this->assertInstanceOf(
            $expected,
            (new FindTypeFromAst())->__invoke($input)
        );
    }

    public function testFindTypeFromAstReturnsNull()
    {
        $this->assertNull((new FindTypeFromAst())->__invoke(null));
    }
}
