<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflector;

use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PhpParser\Token;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\SourceLocator\Ast\Parser\MemoizingParser;

use function array_map;
use function array_unique;
use function count;
use function range;
use function spl_object_id;
use function uniqid;

#[CoversClass(MemoizingParser::class)]
class MemoizingParserTest extends TestCase
{
    public function testParseAndGetTokens(): void
    {
        $wrappedParser = $this->createMock(Parser::class);

        $randomCodeStrings = array_unique(array_map(
            static fn (): string => uniqid('code', true),
            range(0, 100),
        ));

        $randomCodeStringsCount = count($randomCodeStrings);

        $wrappedParser
            ->expects(self::exactly($randomCodeStringsCount))
            ->method('parse')
            ->willReturnCallback(static fn (): array => [new Name('bool')]);
        $wrappedParser
            ->expects(self::exactly($randomCodeStringsCount))
            ->method('getTokens')
            ->willReturn([new Token(1, 'bool', 1, 1)]);

        $parser = new MemoizingParser($wrappedParser);

        $producedNodes = array_map([$parser, 'parse'], $randomCodeStrings);

        self::assertCount($randomCodeStringsCount, $producedNodes);

        foreach ($producedNodes as $parsed) {
            self::assertCount(1, $parsed);
            self::assertInstanceOf(Node::class, $parsed[0]);
        }

        $nodeIdentifiers = array_map(
            static fn (array $nodes): int => spl_object_id($nodes[0]),
            $producedNodes,
        );

        self::assertCount(count($nodeIdentifiers), array_unique($nodeIdentifiers), 'No duplicate nodes allowed');
        self::assertEquals($producedNodes, array_map([$parser, 'parse'], $randomCodeStrings));
        self::assertCount(1, $parser->getTokens());
    }

    public function testParsedCodeIsDifferentAtEachParserLookup(): void
    {
        $code          = '<?php echo "hello world";';
        $wrappedParser = (new ParserFactory())->createForNewestSupportedVersion();

        $parser = new MemoizingParser($wrappedParser);

        self::assertEquals(
            $wrappedParser->parse($code),
            $parser->parse($code),
        );
        self::assertEquals(
            $parser->parse($code),
            $parser->parse($code),
            'Equal tree is produced at each iteration',
        );
        self::assertNotSame(
            $parser->parse($code),
            $parser->parse($code),
            'Each time a tree is requested, a new copy is provided',
        );
    }
}
