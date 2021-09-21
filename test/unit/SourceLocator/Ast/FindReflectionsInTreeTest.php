<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflector;

use PhpParser\Node;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\Identifier\IdentifierType;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionConstant;
use Roave\BetterReflection\Reflection\ReflectionFunction;
use Roave\BetterReflection\Reflector\Exception\IdentifierNotFound;
use Roave\BetterReflection\Reflector\FunctionReflector;
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
    private function getAstForSource(LocatedSource $source): array
    {
        return BetterReflectionSingleton::instance()->phpParser()->parse($source->getSource());
    }

    public function testInvokeDoesNotCallReflectNodesWhenNoNodesFoundInEmptyAst(): void
    {
        $strategy = $this->createMock(NodeToReflection::class);

        $strategy->expects($this->never())
            ->method('__invoke');

        $reflector     = $this->createMock(Reflector::class);
        $locatedSource = new LocatedSource('<?php', null);

        self::assertSame(
            [],
            $this->createFindReflectionsInTree($strategy)->__invoke(
                $reflector,
                $this->getAstForSource($locatedSource),
                new IdentifierType(IdentifierType::IDENTIFIER_CLASS),
                $locatedSource,
            ),
        );
    }

    public function testInvokeDoesNotCallReflectNodesWhenNoNodesFoundInPopulatedAst(): void
    {
        $strategy = $this->createMock(NodeToReflection::class);

        $strategy->expects($this->never())
            ->method('__invoke');

        $reflector     = $this->createMock(Reflector::class);
        $locatedSource = new LocatedSource('<?php echo "Hello world";', null);

        self::assertSame(
            [],
            $this->createFindReflectionsInTree($strategy)->__invoke(
                $reflector,
                $this->getAstForSource($locatedSource),
                new IdentifierType(IdentifierType::IDENTIFIER_CLASS),
                $locatedSource,
            ),
        );
    }

    public function testInvokeCallsReflectNodesForClassWithoutNamespace(): void
    {
        $strategy = $this->createMock(NodeToReflection::class);

        $mockReflection = $this->createMock(ReflectionClass::class);

        $strategy->expects($this->once())
            ->method('__invoke')
            ->will($this->returnValue($mockReflection));

        $reflector     = $this->createMock(Reflector::class);
        $locatedSource = new LocatedSource('<?php class Foo {}', null);

        self::assertSame(
            [$mockReflection],
            $this->createFindReflectionsInTree($strategy)->__invoke(
                $reflector,
                $this->getAstForSource($locatedSource),
                new IdentifierType(IdentifierType::IDENTIFIER_CLASS),
                $locatedSource,
            ),
        );
    }

    public function testInvokeCallsReflectNodesForNamespacedClass(): void
    {
        $strategy = $this->createMock(NodeToReflection::class);

        $mockReflection = $this->createMock(ReflectionClass::class);

        $strategy->expects($this->once())
            ->method('__invoke')
            ->will($this->returnValue($mockReflection));

        $reflector     = $this->createMock(Reflector::class);
        $locatedSource = new LocatedSource('<?php namespace Foo { class Bar {} }', null);

        self::assertSame(
            [$mockReflection],
            $this->createFindReflectionsInTree($strategy)->__invoke(
                $reflector,
                $this->getAstForSource($locatedSource),
                new IdentifierType(IdentifierType::IDENTIFIER_CLASS),
                $locatedSource,
            ),
        );
    }

    public function testInvokeCallsReflectNodesForFunction(): void
    {
        $strategy = $this->createMock(NodeToReflection::class);

        $mockReflection = $this->createMock(ReflectionFunction::class);

        $strategy->expects($this->once())
            ->method('__invoke')
            ->will($this->returnValue($mockReflection));

        $reflector     = $this->createMock(Reflector::class);
        $locatedSource = new LocatedSource('<?php function foo() {}', null);

        self::assertSame(
            [$mockReflection],
            $this->createFindReflectionsInTree($strategy)->__invoke(
                $reflector,
                $this->getAstForSource($locatedSource),
                new IdentifierType(IdentifierType::IDENTIFIER_FUNCTION),
                $locatedSource,
            ),
        );
    }

    public function testInvokeCallsReflectNodesForConstantByConst(): void
    {
        $strategy = $this->createMock(NodeToReflection::class);

        $mockReflection = $this->createMock(ReflectionConstant::class);

        $strategy->expects($this->once())
            ->method('__invoke')
            ->will($this->returnValue($mockReflection));

        $reflector     = $this->createMock(Reflector::class);
        $locatedSource = new LocatedSource('<?php const FOO = 1;', null);

        self::assertSame(
            [$mockReflection],
            $this->createFindReflectionsInTree($strategy)->__invoke(
                $reflector,
                $this->getAstForSource($locatedSource),
                new IdentifierType(IdentifierType::IDENTIFIER_CONSTANT),
                $locatedSource,
            ),
        );
    }

    public function testInvokeCallsReflectNodesForConstantByConstWithMoreConstants(): void
    {
        $strategy = $this->createMock(NodeToReflection::class);

        $mockReflection1 = $this->createMock(ReflectionConstant::class);
        $mockReflection2 = $this->createMock(ReflectionConstant::class);

        $strategy->expects($this->exactly(2))
            ->method('__invoke')
            ->willReturnOnConsecutiveCalls($mockReflection1, $mockReflection2);

        $reflector     = $this->createMock(Reflector::class);
        $locatedSource = new LocatedSource('<?php const FOO = 1, BOO = 2;', null);

        self::assertSame(
            [$mockReflection1, $mockReflection2],
            $this->createFindReflectionsInTree($strategy)->__invoke(
                $reflector,
                $this->getAstForSource($locatedSource),
                new IdentifierType(IdentifierType::IDENTIFIER_CONSTANT),
                $locatedSource,
            ),
        );
    }

    public function testInvokeCallsReflectNodesForConstantByDefine(): void
    {
        $strategy = $this->createMock(NodeToReflection::class);

        $mockReflection = $this->createMock(ReflectionConstant::class);

        $strategy->expects($this->once())
            ->method('__invoke')
            ->will($this->returnValue($mockReflection));

        $reflector     = $this->createMock(Reflector::class);
        $source        = <<<'PHP'
<?php
namespace Foo;

define("FOO", 1);
PHP;
        $locatedSource = new LocatedSource($source, null);

        self::assertSame(
            [$mockReflection],
            $this->createFindReflectionsInTree($strategy)->__invoke(
                $reflector,
                $this->getAstForSource($locatedSource),
                new IdentifierType(IdentifierType::IDENTIFIER_CONSTANT),
                $locatedSource,
            ),
        );
    }

    public function testInvokeCallsReflectNodesForNotGlobalDefine(): void
    {
        $strategy = $this->createMock(NodeToReflection::class);

        $mockFunctionReflection = $this->createMock(ReflectionFunction::class);

        $functionReflector = $this->createMock(FunctionReflector::class);
        $functionReflector->method('reflect')
            ->willReturn($mockFunctionReflection);

        $strategy->expects($this->never())
            ->method('__invoke');

        $reflector     = $this->createMock(Reflector::class);
        $source        = <<<'PHP'
<?php
namespace Foo;

function define() {}

define("FOO", 1);
PHP;
        $locatedSource = new LocatedSource($source, null);

        self::assertSame(
            [],
            $this->createFindReflectionsInTree($strategy, $functionReflector)->__invoke(
                $reflector,
                $this->getAstForSource($locatedSource),
                new IdentifierType(IdentifierType::IDENTIFIER_CONSTANT),
                $locatedSource,
            ),
        );
    }

    public function testNoInvokeCallsReflectNodesForClassConstant(): void
    {
        $strategy = $this->createMock(NodeToReflection::class);

        $mockReflectionClass = $this->createMock(ReflectionClass::class);

        $strategy->expects($this->never())
            ->method('__invoke')
            ->will($this->returnValue($mockReflectionClass));

        $reflector     = $this->createMock(Reflector::class);
        $locatedSource = new LocatedSource('<?php class Foo { const FOO = 1; }', null);

        self::assertSame(
            [],
            $this->createFindReflectionsInTree($strategy)->__invoke(
                $reflector,
                $this->getAstForSource($locatedSource),
                new IdentifierType(IdentifierType::IDENTIFIER_CONSTANT),
                $locatedSource,
            ),
        );
    }

    public function testAnonymousClassCreatedInFunction(): void
    {
        $strategy = $this->createMock(NodeToReflection::class);

        $mockReflectionClass = $this->createMock(ReflectionClass::class);

        $strategy->expects($this->once())
            ->method('__invoke')
            ->willReturnOnConsecutiveCalls($mockReflectionClass);

        $reflector     = $this->createMock(Reflector::class);
        $locatedSource = new LocatedSource('<?php function foo() {return new class {};}', null);

        self::assertSame(
            [$mockReflectionClass],
            $this->createFindReflectionsInTree($strategy)->__invoke(
                $reflector,
                $this->getAstForSource($locatedSource),
                new IdentifierType(IdentifierType::IDENTIFIER_CLASS),
                $locatedSource,
            ),
        );
    }

    private function createFindReflectionsInTree(NodeToReflection $strategy, ?FunctionReflector $functionReflector = null): FindReflectionsInTree
    {
        return new FindReflectionsInTree($strategy, function () use ($functionReflector) {
            if ($functionReflector === null) {
                $functionReflector = $this->createMock(FunctionReflector::class);
                $functionReflector
                    ->method('reflect')
                    ->willThrowException(IdentifierNotFound::fromIdentifier(new Identifier('define', new IdentifierType(IdentifierType::IDENTIFIER_FUNCTION))));
            }

            return $functionReflector;
        });
    }
}
