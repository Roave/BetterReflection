<?php

namespace BetterReflectionTest\Reflector;

use BetterReflection\Identifier\IdentifierType;
use BetterReflection\Reflection\ReflectionClass;
use BetterReflection\Reflection\ReflectionFunction;
use BetterReflection\Reflector\Reflector;
use BetterReflection\SourceLocator\Ast\FindReflectionsInTree;
use BetterReflection\SourceLocator\Ast\Strategy\NodeToReflection;
use BetterReflection\SourceLocator\Located\LocatedSource;
use PhpParser\Parser;
use PhpParser\Lexer;

/**
 * @covers \BetterReflection\SourceLocator\Ast\FindReflectionsInTree
 */
class FindReflectionsInTreeTest extends \PHPUnit_Framework_TestCase
{
    private function getAstForString($php)
    {
        return (new Parser\Multiple([
            new Parser\Php7(new Lexer()),
            new Parser\Php5(new Lexer()),
        ]))->parse($php);
    }

    public function testInvokeDoesNotCallReflectNodesWhenNoNodesFoundInEmptyAst()
    {
        /** @var NodeToReflection|\PHPUnit_Framework_MockObject_MockObject $strategy */
        $strategy = $this->createMock(NodeToReflection::class);

        $strategy->expects($this->never())
            ->method('__invoke');

        /** @var Reflector|\PHPUnit_Framework_MockObject_MockObject $reflector */
        $reflector = $this->createMock(Reflector::class);
        $locatedSource = new LocatedSource('<?php', null);

        $this->assertSame(
            [],
            (new FindReflectionsInTree($strategy))->__invoke(
                $reflector,
                $this->getAstForString($locatedSource->getSource()),
                new IdentifierType(IdentifierType::IDENTIFIER_CLASS),
                $locatedSource
            )
        );
    }

    public function testInvokeDoesNotCallReflectNodesWhenNoNodesFoundInPopulatedAst()
    {
        /** @var NodeToReflection|\PHPUnit_Framework_MockObject_MockObject $strategy */
        $strategy = $this->createMock(NodeToReflection::class);

        $strategy->expects($this->never())
            ->method('__invoke');

        /** @var Reflector|\PHPUnit_Framework_MockObject_MockObject $reflector */
        $reflector = $this->createMock(Reflector::class);
        $locatedSource = new LocatedSource('<?php echo "Hello world";', null);

        $this->assertSame(
            [],
            (new FindReflectionsInTree($strategy))->__invoke(
                $reflector,
                $this->getAstForString($locatedSource->getSource()),
                new IdentifierType(IdentifierType::IDENTIFIER_CLASS),
                $locatedSource
            )
        );
    }

    public function testInvokeCallsReflectNodesForClassWithoutNamespace()
    {
        /** @var NodeToReflection|\PHPUnit_Framework_MockObject_MockObject $strategy */
        $strategy = $this->createMock(NodeToReflection::class);

        $mockReflection = $this->createMock(ReflectionClass::class);

        $strategy->expects($this->once())
            ->method('__invoke')
            ->will($this->returnValue($mockReflection));

        /** @var Reflector|\PHPUnit_Framework_MockObject_MockObject $reflector */
        $reflector = $this->createMock(Reflector::class);
        $locatedSource = new LocatedSource('<?php class Foo {}', null);

        $this->assertSame(
            [
                $mockReflection,
            ],
            (new FindReflectionsInTree($strategy))->__invoke(
                $reflector,
                $this->getAstForString($locatedSource->getSource()),
                new IdentifierType(IdentifierType::IDENTIFIER_CLASS),
                $locatedSource
            )
        );
    }

    public function testInvokeCallsReflectNodesForNamespacedClass()
    {
        /** @var NodeToReflection|\PHPUnit_Framework_MockObject_MockObject $strategy */
        $strategy = $this->createMock(NodeToReflection::class);

        $mockReflection = $this->createMock(ReflectionClass::class);

        $strategy->expects($this->once())
            ->method('__invoke')
            ->will($this->returnValue($mockReflection));

        /** @var Reflector|\PHPUnit_Framework_MockObject_MockObject $reflector */
        $reflector = $this->createMock(Reflector::class);
        $locatedSource = new LocatedSource('<?php namespace Foo { class Bar {} }', null);

        $this->assertSame(
            [
                $mockReflection,
            ],
            (new FindReflectionsInTree($strategy))->__invoke(
                $reflector,
                $this->getAstForString($locatedSource->getSource()),
                new IdentifierType(IdentifierType::IDENTIFIER_CLASS),
                $locatedSource
            )
        );
    }

    public function testInvokeCallsReflectNodesForFunction()
    {
        /** @var NodeToReflection|\PHPUnit_Framework_MockObject_MockObject $strategy */
        $strategy = $this->createMock(NodeToReflection::class);

        $mockReflection = $this->createMock(ReflectionFunction::class);

        $strategy->expects($this->once())
            ->method('__invoke')
            ->will($this->returnValue($mockReflection));

        /** @var Reflector|\PHPUnit_Framework_MockObject_MockObject $reflector */
        $reflector = $this->createMock(Reflector::class);
        $locatedSource = new LocatedSource('<?php function foo() {}', null);

        $this->assertSame(
            [
                $mockReflection,
            ],
            (new FindReflectionsInTree($strategy))->__invoke(
                $reflector,
                $this->getAstForString($locatedSource->getSource()),
                new IdentifierType(IdentifierType::IDENTIFIER_FUNCTION),
                $locatedSource
            )
        );
    }
}
