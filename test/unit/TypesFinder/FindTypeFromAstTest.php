<?php
declare(strict_types=1);

namespace Roave\BetterReflectionTest\TypesFinder;

use Roave\BetterReflection\SourceLocator\Located\LocatedSource;
use Roave\BetterReflection\TypesFinder\FindTypeFromAst;
use PhpParser\Node\Name;
use phpDocumentor\Reflection\Types;

/**
 * @covers \Roave\BetterReflection\TypesFinder\FindTypeFromAst
 */
class FindTypeFromAstTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return array
     */
    public function findTypeFromAstTypeProvider() : array
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
     * @param string $toStringValue
     * @dataProvider findTypeFromAstTypeProvider
     */
    public function testFindTypeFromAst($input, string $expected, string $toStringValue) : void
    {
        $resolvedType = (new FindTypeFromAst())->__invoke(
            $input,
            new LocatedSource('<?php', null),
            'MyNamespace'
        );

        self::assertInstanceOf($expected, $resolvedType);
        self::assertSame($toStringValue, (string)$resolvedType);
    }

    public function testFindTypeFromAstReturnsNull() : void
    {
        self::assertNull(
            (new FindTypeFromAst())->__invoke(
                null,
                new LocatedSource('<?php', null)
            )
        );
    }
}
