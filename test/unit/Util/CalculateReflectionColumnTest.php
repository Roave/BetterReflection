<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Util;

use PhpParser\Node;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\Util\CalculateReflectionColum;
use Roave\BetterReflection\Util\Exception\InvalidNodePosition;
use Roave\BetterReflection\Util\Exception\NoNodePosition;

/**
 * @covers \Roave\BetterReflection\Util\CalculateReflectionColum
 */
class CalculateReflectionColumnTest extends TestCase
{
    public function testGetStartColumn() : void
    {
        $source = "<?php\n    class Foo {}";

        $node = $this->createMock(Node::class);
        $node
            ->method('hasAttribute')
            ->with('startFilePos')
            ->willReturn(true);
        $node
            ->method('getAttribute')
            ->with('startFilePos')
            ->willReturn(10);

        self::assertSame(5, CalculateReflectionColum::getStartColumn($source, $node));
    }

    public function testGetStartColumnIfAtTheBeginningOfLine() : void
    {
        $source = "<?php\nclass Foo {}";

        $node = $this->createMock(Node::class);
        $node
            ->method('hasAttribute')
            ->with('startFilePos')
            ->willReturn(true);
        $node
            ->method('getAttribute')
            ->with('startFilePos')
            ->willReturn(6);

        self::assertSame(1, CalculateReflectionColum::getStartColumn($source, $node));
    }

    public function testGetStartColumnIfOneLineSource() : void
    {
        $source = '<?php class Foo {}';

        $node = $this->createMock(Node::class);
        $node
            ->method('hasAttribute')
            ->with('startFilePos')
            ->willReturn(true);
        $node
            ->method('getAttribute')
            ->with('startFilePos')
            ->willReturn(6);

        self::assertSame(7, CalculateReflectionColum::getStartColumn($source, $node));
    }

    public function testGetStartColumnThrowsExceptionIfInvalidPosition() : void
    {
        $this->expectException(InvalidNodePosition::class);

        $node = $this->createMock(Node::class);
        $node
            ->method('hasAttribute')
            ->with('startFilePos')
            ->willReturn(true);
        $node
            ->method('getAttribute')
            ->with('startFilePos')
            ->willReturn(10000);

        CalculateReflectionColum::getStartColumn('', $node);
    }

    public function testGetStartColumnThrowsExceptionIfNoPosition() : void
    {
        $this->expectException(NoNodePosition::class);

        $node = $this->createMock(Node::class);
        $node
            ->method('hasAttribute')
            ->with('startFilePos')
            ->willReturn(false);

        CalculateReflectionColum::getStartColumn('', $node);
    }

    public function testGetEndColumn() : void
    {
        $source = "<?php\n    class Foo {}";

        $node = $this->createMock(Node::class);
        $node
            ->method('hasAttribute')
            ->with('endFilePos')
            ->willReturn(true);
        $node
            ->method('getAttribute')
            ->with('endFilePos')
            ->willReturn(21);

        self::assertSame(16, CalculateReflectionColum::getEndColumn($source, $node));
    }

    public function testGetEndColumnIfAtTheEndOfLine() : void
    {
        $source = "<?php\nclass Foo {}";

        $node = $this->createMock(Node::class);
        $node
            ->method('hasAttribute')
            ->with('endFilePos')
            ->willReturn(true);
        $node
            ->method('getAttribute')
            ->with('endFilePos')
            ->willReturn(17);

        self::assertSame(12, CalculateReflectionColum::getEndColumn($source, $node));
    }

    public function testGetEndColumnIfOneLineSource() : void
    {
        $source = '<?php class Foo {}';

        $node = $this->createMock(Node::class);
        $node
            ->method('hasAttribute')
            ->with('endFilePos')
            ->willReturn(true);
        $node
            ->method('getAttribute')
            ->with('endFilePos')
            ->willReturn(17);

        self::assertSame(18, CalculateReflectionColum::getEndColumn($source, $node));
    }

    public function testGetEndColumnThrowsExceptionIfInvalidPosition() : void
    {
        $this->expectException(InvalidNodePosition::class);

        $node = $this->createMock(Node::class);
        $node
            ->method('hasAttribute')
            ->with('endFilePos')
            ->willReturn(true);
        $node
            ->method('getAttribute')
            ->with('endFilePos')
            ->willReturn(10000);

        CalculateReflectionColum::getEndColumn('', $node);
    }

    public function testGetEndColumnThrowsExceptionIfNoPosition() : void
    {
        $this->expectException(NoNodePosition::class);

        $node = $this->createMock(Node::class);
        $node
            ->method('hasAttribute')
            ->with('endFilePos')
            ->willReturn(false);

        CalculateReflectionColum::getEndColumn('', $node);
    }
}
