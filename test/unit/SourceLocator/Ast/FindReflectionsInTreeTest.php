<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflector;

use PhpParser\Node;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\Identifier\IdentifierType;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionConstant;
use Roave\BetterReflection\Reflection\ReflectionFunction;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\Ast\FindReflectionsInTree;
use Roave\BetterReflection\SourceLocator\Ast\Strategy\NodeToReflection;
use Roave\BetterReflection\SourceLocator\Located\LocatedSource;
use Roave\BetterReflectionTest\BetterReflectionSingleton;

/**
 * @covers \Roave\BetterReflection\SourceLocator\Ast\FindReflectionsInTree
 */
class FindReflectionsInTreeTest extends TestCase
{
    /**
     * @return Node[]
     */
    private function getAstForSource(LocatedSource $source) : array
    {
        return BetterReflectionSingleton::instance()->phpParser()->parse($source->getSource());
    }

    public function testInvokeDoesNotCallReflectNodesWhenNoNodesFoundInEmptyAst() : void
    {
        /** @var NodeToReflection|MockObject $strategy */
        $strategy = $this->createMock(NodeToReflection::class);

        $strategy->expects($this->never())
            ->method('__invoke');

        /** @var Reflector|MockObject $reflector */
        $reflector     = $this->createMock(Reflector::class);
        $locatedSource = new LocatedSource('<?php', null);

        self::assertSame(
            [],
            (new FindReflectionsInTree($strategy))->__invoke(
                $reflector,
                $this->getAstForSource($locatedSource),
                new IdentifierType(IdentifierType::IDENTIFIER_CLASS),
                $locatedSource
            )
        );
    }

    public function testInvokeDoesNotCallReflectNodesWhenNoNodesFoundInPopulatedAst() : void
    {
        /** @var NodeToReflection|MockObject $strategy */
        $strategy = $this->createMock(NodeToReflection::class);

        $strategy->expects($this->never())
            ->method('__invoke');

        /** @var Reflector|MockObject $reflector */
        $reflector     = $this->createMock(Reflector::class);
        $locatedSource = new LocatedSource('<?php echo "Hello world";', null);

        self::assertSame(
            [],
            (new FindReflectionsInTree($strategy))->__invoke(
                $reflector,
                $this->getAstForSource($locatedSource),
                new IdentifierType(IdentifierType::IDENTIFIER_CLASS),
                $locatedSource
            )
        );
    }

    public function testInvokeCallsReflectNodesForClassWithoutNamespace() : void
    {
        /** @var NodeToReflection|MockObject $strategy */
        $strategy = $this->createMock(NodeToReflection::class);

        $mockReflection = $this->createMock(ReflectionClass::class);

        $strategy->expects($this->once())
            ->method('__invoke')
            ->will($this->returnValue($mockReflection));

        /** @var Reflector|MockObject $reflector */
        $reflector     = $this->createMock(Reflector::class);
        $locatedSource = new LocatedSource('<?php class Foo {}', null);

        self::assertSame(
            [$mockReflection],
            (new FindReflectionsInTree($strategy))->__invoke(
                $reflector,
                $this->getAstForSource($locatedSource),
                new IdentifierType(IdentifierType::IDENTIFIER_CLASS),
                $locatedSource
            )
        );
    }

    public function testInvokeCallsReflectNodesForNamespacedClass() : void
    {
        /** @var NodeToReflection|MockObject $strategy */
        $strategy = $this->createMock(NodeToReflection::class);

        $mockReflection = $this->createMock(ReflectionClass::class);

        $strategy->expects($this->once())
            ->method('__invoke')
            ->will($this->returnValue($mockReflection));

        /** @var Reflector|MockObject $reflector */
        $reflector     = $this->createMock(Reflector::class);
        $locatedSource = new LocatedSource('<?php namespace Foo { class Bar {} }', null);

        self::assertSame(
            [$mockReflection],
            (new FindReflectionsInTree($strategy))->__invoke(
                $reflector,
                $this->getAstForSource($locatedSource),
                new IdentifierType(IdentifierType::IDENTIFIER_CLASS),
                $locatedSource
            )
        );
    }

    public function testInvokeCallsReflectNodesForFunction() : void
    {
        /** @var NodeToReflection|MockObject $strategy */
        $strategy = $this->createMock(NodeToReflection::class);

        $mockReflection = $this->createMock(ReflectionFunction::class);

        $strategy->expects($this->once())
            ->method('__invoke')
            ->will($this->returnValue($mockReflection));

        /** @var Reflector|MockObject $reflector */
        $reflector     = $this->createMock(Reflector::class);
        $locatedSource = new LocatedSource('<?php function foo() {}', null);

        self::assertSame(
            [$mockReflection],
            (new FindReflectionsInTree($strategy))->__invoke(
                $reflector,
                $this->getAstForSource($locatedSource),
                new IdentifierType(IdentifierType::IDENTIFIER_FUNCTION),
                $locatedSource
            )
        );
    }

    public function testInvokeCallsReflectNodesForConstantByConst() : void
    {
        /** @var NodeToReflection|MockObject $strategy */
        $strategy = $this->createMock(NodeToReflection::class);

        $mockReflection = $this->createMock(ReflectionConstant::class);

        $strategy->expects($this->once())
            ->method('__invoke')
            ->will($this->returnValue($mockReflection));

        /** @var Reflector|MockObject $reflector */
        $reflector     = $this->createMock(Reflector::class);
        $locatedSource = new LocatedSource('<?php const FOO = 1;', null);

        self::assertSame(
            [$mockReflection],
            (new FindReflectionsInTree($strategy))->__invoke(
                $reflector,
                $this->getAstForSource($locatedSource),
                new IdentifierType(IdentifierType::IDENTIFIER_CONSTANT),
                $locatedSource
            )
        );
    }

    public function testInvokeCallsReflectNodesForConstantByConstWithMoreConstants() : void
    {
        /** @var NodeToReflection|MockObject $strategy */
        $strategy = $this->createMock(NodeToReflection::class);

        $mockReflection1 = $this->createMock(ReflectionConstant::class);
        $mockReflection2 = $this->createMock(ReflectionConstant::class);

        $strategy->expects($this->exactly(2))
            ->method('__invoke')
            ->willReturnOnConsecutiveCalls($mockReflection1, $mockReflection2);

        /** @var Reflector|MockObject $reflector */
        $reflector     = $this->createMock(Reflector::class);
        $locatedSource = new LocatedSource('<?php const FOO = 1, BOO = 2;', null);

        self::assertSame(
            [$mockReflection1, $mockReflection2],
            (new FindReflectionsInTree($strategy))->__invoke(
                $reflector,
                $this->getAstForSource($locatedSource),
                new IdentifierType(IdentifierType::IDENTIFIER_CONSTANT),
                $locatedSource
            )
        );
    }

    public function testInvokeCallsReflectNodesForConstantByDefine() : void
    {
        /** @var NodeToReflection|MockObject $strategy */
        $strategy = $this->createMock(NodeToReflection::class);

        $mockReflection = $this->createMock(ReflectionConstant::class);

        $strategy->expects($this->once())
            ->method('__invoke')
            ->will($this->returnValue($mockReflection));

        /** @var Reflector|MockObject $reflector */
        $reflector     = $this->createMock(Reflector::class);
        $locatedSource = new LocatedSource('<?php define("FOO", 1);', null);

        self::assertSame(
            [$mockReflection],
            (new FindReflectionsInTree($strategy))->__invoke(
                $reflector,
                $this->getAstForSource($locatedSource),
                new IdentifierType(IdentifierType::IDENTIFIER_CONSTANT),
                $locatedSource
            )
        );
    }

    public function testNoInvokeCallsReflectNodesForClassConstant() : void
    {
        /** @var NodeToReflection|MockObject $strategy */
        $strategy = $this->createMock(NodeToReflection::class);

        $mockReflectionClass = $this->createMock(ReflectionClass::class);

        $strategy->expects($this->once())
            ->method('__invoke')
            ->will($this->returnValue($mockReflectionClass));

        /** @var Reflector|MockObject $reflector */
        $reflector     = $this->createMock(Reflector::class);
        $locatedSource = new LocatedSource('<?php class Foo { const FOO = 1; }', null);

        self::assertSame(
            [],
            (new FindReflectionsInTree($strategy))->__invoke(
                $reflector,
                $this->getAstForSource($locatedSource),
                new IdentifierType(IdentifierType::IDENTIFIER_CONSTANT),
                $locatedSource
            )
        );
    }

    public function testAnonymousClassCreatedInFunction() : void
    {
        /** @var NodeToReflection|MockObject $strategy */
        $strategy = $this->createMock(NodeToReflection::class);

        $mockReflectionFunction = $this->createMock(ReflectionFunction::class);
        $mockReflectionClass    = $this->createMock(ReflectionClass::class);

        $strategy->expects($this->exactly(2))
            ->method('__invoke')
            ->willReturnOnConsecutiveCalls($mockReflectionFunction, $mockReflectionClass);

        /** @var Reflector|MockObject $reflector */
        $reflector     = $this->createMock(Reflector::class);
        $locatedSource = new LocatedSource('<?php function foo() {return new class {};}', null);

        self::assertSame(
            [$mockReflectionClass],
            (new FindReflectionsInTree($strategy))->__invoke(
                $reflector,
                $this->getAstForSource($locatedSource),
                new IdentifierType(IdentifierType::IDENTIFIER_CLASS),
                $locatedSource
            )
        );
    }
}
