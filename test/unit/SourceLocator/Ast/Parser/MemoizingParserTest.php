<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflector;

use PhpParser\Node;
use PhpParser\Parser;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\SourceLocator\Ast\Parser\MemoizingParser;
use function array_map;
use function array_unique;
use function count;
use function range;
use function spl_object_hash;
use function uniqid;

/**
 * @covers \Roave\BetterReflection\SourceLocator\Ast\Parser\MemoizingParser
 */
class MemoizingParserTest extends TestCase
{
    public function testParse() : void
    {
        $wrappedParser = $this->createMock(Parser::class);

        $randomCodeStrings = array_unique(array_map(
            static function () : string {
                return uniqid('code', true);
            },
            range(0, 100),
        ));

        $randomCodeStringsCount = count($randomCodeStrings);

        $wrappedParser
            ->expects(self::exactly($randomCodeStringsCount))
            ->method('parse')
            ->willReturnCallback(function () : array {
                return [$this->createMock(Node::class)];
            });

        $parser = new MemoizingParser($wrappedParser);

        $producedNodes = array_map([$parser, 'parse'], $randomCodeStrings);

        self::assertCount($randomCodeStringsCount, $producedNodes);

        foreach ($producedNodes as $parsed) {
            self::assertCount(1, $parsed);
            self::assertInstanceOf(Node::class, $parsed[0]);
        }

        $nodeIdentifiers = array_map(
            static function (array $nodes) : string {
                return spl_object_hash($nodes[0]);
            },
            $producedNodes,
        );

        self::assertCount(count($nodeIdentifiers), array_unique($nodeIdentifiers), 'No duplicate nodes allowed');
        self::assertSame($producedNodes, array_map([$parser, 'parse'], $randomCodeStrings));
    }
}
