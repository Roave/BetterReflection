<?php
declare(strict_types=1);

namespace Rector\BetterReflectionTest\Reflector;

use PhpParser\Lexer;
use PhpParser\Node;
use PhpParser\Parser;
use PHPUnit\Framework\TestCase;
use Rector\BetterReflection\Reflection\ReflectionClass;
use Rector\BetterReflection\Reflection\ReflectionFunction;
use Rector\BetterReflection\Reflector\Reflector;
use Rector\BetterReflection\SourceLocator\Ast\Strategy\NodeToReflection;
use Rector\BetterReflection\SourceLocator\Located\LocatedSource;

/**
 * @covers \Rector\BetterReflection\SourceLocator\Ast\Strategy\NodeToReflection
 */
class NodeToReflectionTest extends TestCase
{
    private function getFirstAstNodeInString(string $php) : Node
    {
        $nodes = (new Parser\Multiple([
            new Parser\Php7(new Lexer()),
            new Parser\Php5(new Lexer()),
        ]))->parse($php);
        return \reset($nodes);
    }

    public function testReturnsReflectionForClassNode() : void
    {
        /** @var Reflector|\PHPUnit_Framework_MockObject_MockObject $reflector */
        $reflector = $this->createMock(Reflector::class);

        $locatedSource = new LocatedSource('<?php class Foo {}', null);

        $reflection = (new NodeToReflection())->__invoke(
            $reflector,
            $this->getFirstAstNodeInString($locatedSource->getSource()),
            $locatedSource,
            null
        );

        self::assertInstanceOf(ReflectionClass::class, $reflection);
        self::assertSame('Foo', $reflection->getName());
    }

    public function testReturnsReflectionForTraitNode() : void
    {
        /** @var Reflector|\PHPUnit_Framework_MockObject_MockObject $reflector */
        $reflector = $this->createMock(Reflector::class);

        $locatedSource = new LocatedSource('<?php trait Foo {}', null);

        $reflection = (new NodeToReflection())->__invoke(
            $reflector,
            $this->getFirstAstNodeInString($locatedSource->getSource()),
            $locatedSource,
            null
        );

        self::assertInstanceOf(ReflectionClass::class, $reflection);
        self::assertSame('Foo', $reflection->getName());
        self::assertTrue($reflection->isTrait());
    }

    public function testReturnsReflectionForInterfaceNode() : void
    {
        /** @var Reflector|\PHPUnit_Framework_MockObject_MockObject $reflector */
        $reflector = $this->createMock(Reflector::class);

        $locatedSource = new LocatedSource('<?php interface Foo {}', null);

        $reflection = (new NodeToReflection())->__invoke(
            $reflector,
            $this->getFirstAstNodeInString($locatedSource->getSource()),
            $locatedSource,
            null
        );

        self::assertInstanceOf(ReflectionClass::class, $reflection);
        self::assertSame('Foo', $reflection->getName());
        self::assertTrue($reflection->isInterface());
    }

    public function testReturnsReflectionForFunctionNode() : void
    {
        /** @var Reflector|\PHPUnit_Framework_MockObject_MockObject $reflector */
        $reflector = $this->createMock(Reflector::class);

        $locatedSource = new LocatedSource('<?php function foo(){}', null);

        $reflection = (new NodeToReflection())->__invoke(
            $reflector,
            $this->getFirstAstNodeInString($locatedSource->getSource()),
            $locatedSource,
            null
        );

        self::assertInstanceOf(ReflectionFunction::class, $reflection);
        self::assertSame('foo', $reflection->getName());
    }

    public function testReturnsNullWhenIncompatibleNodeFound() : void
    {
        /** @var Reflector|\PHPUnit_Framework_MockObject_MockObject $reflector */
        $reflector = $this->createMock(Reflector::class);

        $locatedSource = new LocatedSource('<?php echo "Hello world";', null);

        self::assertNull((new NodeToReflection())->__invoke(
            $reflector,
            $this->getFirstAstNodeInString($locatedSource->getSource()),
            $locatedSource,
            null
        ));
    }
}
