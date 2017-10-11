<?php
declare(strict_types=1);

namespace Rector\BetterReflectionTest\Util\Exception;

use PhpParser\Lexer;
use PhpParser\Node\Scalar\LNumber;
use PHPUnit\Framework\TestCase;
use Rector\BetterReflection\Util\Exception\NoNodePosition;

/**
 * @covers \Rector\BetterReflection\Util\Exception\NoNodePosition
 */
class NoNodePositionTest extends TestCase
{
    public function testFromPosition() : void
    {
        $node = new LNumber(123);

        $exception = NoNodePosition::fromNode($node);

        self::assertInstanceOf(NoNodePosition::class, $exception);
        self::assertSame(\sprintf('%s doesn\'t contain position. Your %s is not configured properly', \get_class($node), Lexer::class), $exception->getMessage());
    }
}
