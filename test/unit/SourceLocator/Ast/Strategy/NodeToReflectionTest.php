<?php

namespace Roave\BetterReflectionTest\Reflector;

use PhpParser\Node;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionFunction;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\Ast\Strategy\NodeToReflection;
use Roave\BetterReflection\SourceLocator\Located\LocatedSource;
use PhpParser\Parser;
use PhpParser\Lexer;

/**
 * @covers \Roave\BetterReflection\SourceLocator\Ast\Strategy\NodeToReflection
 */
class NodeToReflectionTest extends \PHPUnit_Framework_TestCase
{
    private function getFirstAstNodeInString($php) : Node
    {
        $nodes = (new Parser\Multiple([
            new Parser\Php7(new Lexer()),
            new Parser\Php5(new Lexer()),
        ]))->parse($php);
        return reset($nodes);
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
