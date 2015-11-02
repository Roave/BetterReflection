<?php

namespace BetterReflectionTest\Reflector;

use BetterReflection\Reflection\ReflectionClass;
use BetterReflection\Reflection\ReflectionFunction;
use BetterReflection\Reflector\Reflector;
use BetterReflection\SourceLocator\Ast\Strategy\NodeToReflection;
use BetterReflection\SourceLocator\Located\LocatedSource;
use PhpParser\Parser;
use PhpParser\Lexer;

/**
 * @covers \BetterReflection\SourceLocator\Ast\Strategy\NodeToReflection
 */
class NodeToReflectionTest extends \PHPUnit_Framework_TestCase
{
    private function getFirstAstNodeInString($php)
    {
        return reset((new Parser\Multiple([
            new Parser\Php7(new Lexer()),
            new Parser\Php5(new Lexer()),
        ]))->parse($php));
    }

    public function testReturnsReflectionForClassNode()
    {
        /** @var Reflector|\PHPUnit_Framework_MockObject_MockObject $reflector */
        $reflector = $this->getMock(Reflector::class);

        $locatedSource = new LocatedSource('<?php class Foo {}', null);

        $reflection = (new NodeToReflection())->__invoke(
            $reflector,
            $this->getFirstAstNodeInString($locatedSource->getSource()),
            $locatedSource,
            null
        );

        $this->assertInstanceOf(ReflectionClass::class, $reflection);
        $this->assertSame('Foo', $reflection->getName());
    }

    public function testReturnsReflectionForTraitNode()
    {
        /** @var Reflector|\PHPUnit_Framework_MockObject_MockObject $reflector */
        $reflector = $this->getMock(Reflector::class);

        $locatedSource = new LocatedSource('<?php trait Foo {}', null);

        $reflection = (new NodeToReflection())->__invoke(
            $reflector,
            $this->getFirstAstNodeInString($locatedSource->getSource()),
            $locatedSource,
            null
        );

        $this->assertInstanceOf(ReflectionClass::class, $reflection);
        $this->assertSame('Foo', $reflection->getName());
        $this->assertTrue($reflection->isTrait());
    }

    public function testReturnsReflectionForInterfaceNode()
    {
        /** @var Reflector|\PHPUnit_Framework_MockObject_MockObject $reflector */
        $reflector = $this->getMock(Reflector::class);

        $locatedSource = new LocatedSource('<?php interface Foo {}', null);

        $reflection = (new NodeToReflection())->__invoke(
            $reflector,
            $this->getFirstAstNodeInString($locatedSource->getSource()),
            $locatedSource,
            null
        );

        $this->assertInstanceOf(ReflectionClass::class, $reflection);
        $this->assertSame('Foo', $reflection->getName());
        $this->assertTrue($reflection->isInterface());
    }

    public function testReturnsReflectionForFunctionNode()
    {
        /** @var Reflector|\PHPUnit_Framework_MockObject_MockObject $reflector */
        $reflector = $this->getMock(Reflector::class);

        $locatedSource = new LocatedSource('<?php function foo(){}', null);

        $reflection = (new NodeToReflection())->__invoke(
            $reflector,
            $this->getFirstAstNodeInString($locatedSource->getSource()),
            $locatedSource,
            null
        );

        $this->assertInstanceOf(ReflectionFunction::class, $reflection);
        $this->assertSame('foo', $reflection->getName());
    }

    public function testReturnsNullWhenIncompatibleNodeFound()
    {
        /** @var Reflector|\PHPUnit_Framework_MockObject_MockObject $reflector */
        $reflector = $this->getMock(Reflector::class);

        $locatedSource = new LocatedSource('<?php echo "Hello world";', null);

        $this->assertNull((new NodeToReflection())->__invoke(
            $reflector,
            $this->getFirstAstNodeInString($locatedSource->getSource()),
            $locatedSource,
            null
        ));
    }
}
