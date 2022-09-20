<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflector;

use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\Parser;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionConstant;
use Roave\BetterReflection\Reflection\ReflectionEnum;
use Roave\BetterReflection\Reflection\ReflectionFunction;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\Ast\Strategy\NodeToReflection;
use Roave\BetterReflection\SourceLocator\Located\LocatedSource;
use Roave\BetterReflectionTest\BetterReflectionSingleton;

use function reset;

/** @covers \Roave\BetterReflection\SourceLocator\Ast\Strategy\NodeToReflection */
class NodeToReflectionTest extends TestCase
{
    private Parser $phpParser;

    private NodeTraverser $nodeTraverser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->phpParser = BetterReflectionSingleton::instance()->phpParser();

        $this->nodeTraverser = new NodeTraverser();
        $this->nodeTraverser->addVisitor(new NameResolver());
    }

    private function getFirstAstNodeInString(string $php): Node
    {
        $nodes = $this->phpParser->parse($php);

        $this->nodeTraverser->traverse($nodes);

        return reset($nodes);
    }

    public function testReturnsReflectionForClassNode(): void
    {
        $reflector = $this->createMock(Reflector::class);

        $locatedSource = new LocatedSource('<?php class Foo {}', 'Foo');

        $node = $this->getFirstAstNodeInString($locatedSource->getSource());
        self::assertInstanceOf(Node\Stmt\Class_::class, $node);

        $reflection = (new NodeToReflection())->__invoke(
            $reflector,
            $node,
            $locatedSource,
            null,
        );

        self::assertInstanceOf(ReflectionClass::class, $reflection);
        self::assertSame('Foo', $reflection->getName());
    }

    public function testReturnsReflectionForTraitNode(): void
    {
        $reflector = $this->createMock(Reflector::class);

        $locatedSource = new LocatedSource('<?php trait Foo {}', 'Foo');

        $node = $this->getFirstAstNodeInString($locatedSource->getSource());
        self::assertInstanceOf(Node\Stmt\Trait_::class, $node);

        $reflection = (new NodeToReflection())->__invoke(
            $reflector,
            $node,
            $locatedSource,
            null,
        );

        self::assertInstanceOf(ReflectionClass::class, $reflection);
        self::assertSame('Foo', $reflection->getName());
        self::assertTrue($reflection->isTrait());
    }

    public function testReturnsReflectionForInterfaceNode(): void
    {
        $reflector = $this->createMock(Reflector::class);

        $locatedSource = new LocatedSource('<?php interface Foo {}', 'Foo');

        $node = $this->getFirstAstNodeInString($locatedSource->getSource());
        self::assertInstanceOf(Node\Stmt\Interface_::class, $node);

        $reflection = (new NodeToReflection())->__invoke(
            $reflector,
            $node,
            $locatedSource,
            null,
        );

        self::assertInstanceOf(ReflectionClass::class, $reflection);
        self::assertSame('Foo', $reflection->getName());
        self::assertTrue($reflection->isInterface());
    }

    public function testReturnsReflectionForEnumNode(): void
    {
        $reflector = $this->createMock(Reflector::class);

        $locatedSource = new LocatedSource('<?php enum Foo {}', 'Foo');

        $node = $this->getFirstAstNodeInString($locatedSource->getSource());
        self::assertInstanceOf(Node\Stmt\Enum_::class, $node);

        $reflection = (new NodeToReflection())->__invoke(
            $reflector,
            $node,
            $locatedSource,
            null,
        );

        self::assertInstanceOf(ReflectionEnum::class, $reflection);
        self::assertSame('Foo', $reflection->getName());
    }

    public function testReturnsReflectionForFunctionNode(): void
    {
        $reflector = $this->createMock(Reflector::class);

        $locatedSource = new LocatedSource('<?php function foo(){}', 'foo');

        $node = $this->getFirstAstNodeInString($locatedSource->getSource());
        self::assertInstanceOf(Node\Stmt\Function_::class, $node);

        $reflection = (new NodeToReflection())->__invoke(
            $reflector,
            $node,
            $locatedSource,
            null,
        );

        self::assertInstanceOf(ReflectionFunction::class, $reflection);
        self::assertSame('foo', $reflection->getName());
    }

    public function testReturnsReflectionForClosureNode(): void
    {
        $reflector = $this->createMock(Reflector::class);

        $locatedSource = new LocatedSource('<?php function() {};', null);

        $node = $this->getFirstAstNodeInString($locatedSource->getSource());
        self::assertInstanceOf(Node\Stmt\Expression::class, $node);
        self::assertInstanceOf(Node\Expr\Closure::class, $node->expr);

        $reflection = (new NodeToReflection())->__invoke(
            $reflector,
            $node->expr,
            $locatedSource,
            null,
        );

        self::assertInstanceOf(ReflectionFunction::class, $reflection);
        self::assertSame(ReflectionFunction::CLOSURE_NAME, $reflection->getName());
    }

    public function testReturnsReflectionForArrowFunctionNode(): void
    {
        $reflector = $this->createMock(Reflector::class);

        $locatedSource = new LocatedSource('<?php fn () => "";', null);

        $node = $this->getFirstAstNodeInString($locatedSource->getSource());
        self::assertInstanceOf(Node\Stmt\Expression::class, $node);
        self::assertInstanceOf(Node\Expr\ArrowFunction::class, $node->expr);

        $reflection = (new NodeToReflection())->__invoke(
            $reflector,
            $node->expr,
            $locatedSource,
            null,
        );

        self::assertInstanceOf(ReflectionFunction::class, $reflection);
        self::assertSame(ReflectionFunction::CLOSURE_NAME, $reflection->getName());
    }

    public function testReturnsReflectionForConstantNodeByConst(): void
    {
        $reflector = $this->createMock(Reflector::class);

        $locatedSource = new LocatedSource('<?php const FOO = 1;', 'FOO');

        $node = $this->getFirstAstNodeInString($locatedSource->getSource());
        self::assertInstanceOf(Node\Stmt\Const_::class, $node);

        $reflection = (new NodeToReflection())->__invoke(
            $reflector,
            $node,
            $locatedSource,
            null,
            0,
        );

        self::assertInstanceOf(ReflectionConstant::class, $reflection);
        self::assertSame('FOO', $reflection->getName());
    }

    public function testReturnsReflectionForConstantNodeByConstWithMoreConstants(): void
    {
        $reflector        = $this->createMock(Reflector::class);
        $nodeToReflection = new NodeToReflection();

        $source = '<?php const FOO = 1, BOO = 2;';

        $node = $this->getFirstAstNodeInString($source);
        self::assertInstanceOf(Node\Stmt\Const_::class, $node);

        $reflection1 = $nodeToReflection->__invoke(
            $reflector,
            $node,
            new LocatedSource($source, 'FOO', null),
            null,
            0,
        );
        $reflection2 = $nodeToReflection->__invoke(
            $reflector,
            $node,
            new LocatedSource($source, 'BOO', null),
            null,
            1,
        );

        self::assertInstanceOf(ReflectionConstant::class, $reflection1);
        self::assertSame('FOO', $reflection1->getName());
        self::assertInstanceOf(ReflectionConstant::class, $reflection2);
        self::assertSame('BOO', $reflection2->getName());
    }

    public function testReturnsReflectionForConstantNodeByDefine(): void
    {
        $reflector = $this->createMock(Reflector::class);

        $locatedSource = new LocatedSource('<?php define("FOO", 1);', 'FOO');

        $node = $this->getFirstAstNodeInString($locatedSource->getSource());
        self::assertInstanceOf(Node\Stmt\Expression::class, $node);
        self::assertInstanceOf(Node\Expr\FuncCall::class, $node->expr);

        $reflection = (new NodeToReflection())->__invoke(
            $reflector,
            $node->expr,
            $locatedSource,
            null,
        );

        self::assertInstanceOf(ReflectionConstant::class, $reflection);
        self::assertSame('FOO', $reflection->getName());
    }
}
