<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection;

use PhpParser\BuilderHelpers;
use PhpParser\Node;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\Reflection\ReflectionConstant;
use Roave\BetterReflection\Reflector\DefaultReflector;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\Located\LocatedSource;
use Roave\BetterReflection\SourceLocator\SourceStubber\SourceStubber;
use Roave\BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\PhpInternalSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;
use Roave\BetterReflectionTest\BetterReflectionSingleton;

use const E_ALL;

/**
 * @covers \Roave\BetterReflection\Reflection\ReflectionConstant
 */
class ReflectionConstantTest extends TestCase
{
    private Locator $astLocator;

    private SourceStubber $sourceStubber;

    protected function setUp(): void
    {
        parent::setUp();

        $configuration       = BetterReflectionSingleton::instance();
        $this->astLocator    = $configuration->astLocator();
        $this->sourceStubber = $configuration->sourceStubber();
    }

    public function testNameMethodsWithNoNamespaceByConst(): void
    {
        $php = '<?php const FOO = 1;';

        $reflector  = new DefaultReflector(new StringSourceLocator($php, $this->astLocator));
        $reflection = $reflector->reflectConstant('FOO');

        self::assertFalse($reflection->inNamespace());
        self::assertSame('FOO', $reflection->getName());
        self::assertSame('', $reflection->getNamespaceName());
        self::assertSame('FOO', $reflection->getShortName());
    }

    public function testNameMethodsWithNoNamespaceByDefine(): void
    {
        $php = '<?php define("FOO", 1);';

        $reflector  = new DefaultReflector(new StringSourceLocator($php, $this->astLocator));
        $reflection = $reflector->reflectConstant('FOO');

        self::assertFalse($reflection->inNamespace());
        self::assertSame('FOO', $reflection->getName());
        self::assertSame('', $reflection->getNamespaceName());
        self::assertSame('FOO', $reflection->getShortName());
    }

    public function testNameMethodsInNamespace(): void
    {
        $php = '<?php namespace A\B { const FOO = 1; }';

        $reflector  = new DefaultReflector(new StringSourceLocator($php, $this->astLocator));
        $reflection = $reflector->reflectConstant('A\B\FOO');

        self::assertTrue($reflection->inNamespace());
        self::assertSame('A\B\FOO', $reflection->getName());
        self::assertSame('A\B', $reflection->getNamespaceName());
        self::assertSame('FOO', $reflection->getShortName());
    }

    public function testNameMethodsInExplicitGlobalNamespace(): void
    {
        $php = '<?php namespace { const FOO = 1; }';

        $reflector  = new DefaultReflector(new StringSourceLocator($php, $this->astLocator));
        $reflection = $reflector->reflectConstant('FOO');

        self::assertFalse($reflection->inNamespace());
        self::assertSame('FOO', $reflection->getName());
        self::assertSame('', $reflection->getNamespaceName());
        self::assertSame('FOO', $reflection->getShortName());
    }

    public function testIsUserDefined(): void
    {
        $php = '<?php const FOO = 1;';

        $reflector  = new DefaultReflector(new StringSourceLocator($php, $this->astLocator));
        $reflection = $reflector->reflectConstant('FOO');

        self::assertTrue($reflection->isUserDefined());
        self::assertFalse($reflection->isInternal());
        self::assertNull($reflection->getExtensionName());
    }

    public function testIsInternal(): void
    {
        $reflector  = new DefaultReflector(new PhpInternalSourceLocator($this->astLocator, $this->sourceStubber));
        $reflection = $reflector->reflectConstant('E_ALL');

        self::assertTrue($reflection->isInternal());
        self::assertFalse($reflection->isUserDefined());
        self::assertSame('Core', $reflection->getExtensionName());
    }

    public function testGetValueByConst(): void
    {
        $php = '<?php const FOO = 1;';

        $reflector  = new DefaultReflector(new StringSourceLocator($php, $this->astLocator));
        $reflection = $reflector->reflectConstant('FOO');

        self::assertSame(1, $reflection->getValue());
    }

    public function testGetValueByDefine(): void
    {
        $php = '<?php define("FOO", E_ALL);';

        $reflector  = new DefaultReflector(new AggregateSourceLocator([
            new StringSourceLocator($php, $this->astLocator),
            new PhpInternalSourceLocator($this->astLocator, $this->sourceStubber),
        ]));
        $reflection = $reflector->reflectConstant('FOO');

        self::assertSame(E_ALL, $reflection->getValue());
    }

    public function testStaticCreationFromNameByConst(): void
    {
        require_once __DIR__ . '/../Fixture/Constants.php';
        $reflection = ReflectionConstant::createFromName('Roave\BetterReflectionTest\Fixture\BY_CONST');

        self::assertSame('Roave\BetterReflectionTest\Fixture\BY_CONST', $reflection->getName());
        self::assertSame('BY_CONST', $reflection->getShortName());
    }

    public function testStaticCreationFromNameByDefine(): void
    {
        require_once __DIR__ . '/../Fixture/Constants.php';
        $reflection = ReflectionConstant::createFromName('BY_DEFINE');

        self::assertSame('BY_DEFINE', $reflection->getName());
        self::assertSame('BY_DEFINE', $reflection->getShortName());
    }

    public function testStaticCreationFromNameByDefineWithNamespace(): void
    {
        require_once __DIR__ . '/../Fixture/Constants.php';
        $reflection = ReflectionConstant::createFromName('Roave\BetterReflectionTest\Fixture\BY_DEFINE');

        self::assertSame('Roave\BetterReflectionTest\Fixture\BY_DEFINE', $reflection->getName());
        self::assertSame('BY_DEFINE', $reflection->getShortName());
    }

    public function testToString(): void
    {
        require_once __DIR__ . '/../Fixture/Constants.php';
        $reflection = ReflectionConstant::createFromName('Roave\BetterReflectionTest\Fixture\BY_CONST');

        self::assertStringMatchesFormat("Constant [ <user> boolean Roave\BetterReflectionTest\Fixture\BY_CONST ] {\n  @@ %s/Fixture/Constants.php 5 - 5\n 1 }", (string) $reflection);
    }

    public function testGetFileName(): void
    {
        $this->markTestSkipped();

        $reflector  = new DefaultReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/Constants.php', $this->astLocator));
        $reflection = $reflector->reflectConstant('Roave\BetterReflectionTest\Fixture\BY_CONST');

        self::assertStringContainsString('Fixture/Constants.php', $reflection->getFileName());
    }

    public function testGetFileNameOfUnlocatedSource(): void
    {
        $php = '<?php const FOO = 1;';

        $reflector  = new DefaultReflector(new StringSourceLocator($php, $this->astLocator));
        $reflection = $reflector->reflectConstant('FOO');

        self::assertNull($reflection->getFileName());
    }

    public function testGetLocatedSource(): void
    {
        $node          = new Node\Stmt\Const_([new Node\Const_('FOO', BuilderHelpers::normalizeValue(1))]);
        $locatedSource = new LocatedSource('<?php const FOO = 1', null);
        $reflector     = new DefaultReflector(new StringSourceLocator('<?php', $this->astLocator));
        $reflection    = ReflectionConstant::createFromNode($reflector, $node, $locatedSource, null, 0);

        self::assertSame($locatedSource, $reflection->getLocatedSource());
    }

    public function testGetDocCommentByConst(): void
    {
        $php = '<?php
            /**
             * @var int
             */
            /** This constant comment should be used. */ 
            const FOO = 1;';

        $reflector  = new DefaultReflector(new StringSourceLocator($php, $this->astLocator));
        $reflection = $reflector->reflectConstant('FOO');

        self::assertStringContainsString('This constant comment should be used.', $reflection->getDocComment());
    }

    public function testGetDocCommentByDefine(): void
    {
        $php = '<?php
            /**
             * @var int
             */
            /** This constant comment should be used. */ 
            define("FOO", 1);';

        $reflector  = new DefaultReflector(new StringSourceLocator($php, $this->astLocator));
        $reflection = $reflector->reflectConstant('FOO');

        self::assertStringContainsString('This constant comment should be used.', $reflection->getDocComment());
    }

    public function startEndLineProvider(): array
    {
        return [
            ["<?php\n\nconst FOO = [\n];\n", 3, 4],
            ["<?php\n\ndefine(\n'FOO',\n1\n);\n", 3, 6],
            ["<?php\n\nconst BOO = 1,\nFOO = 2;\n", 3, 4],
        ];
    }

    /**
     * @dataProvider startEndLineProvider
     */
    public function testStartEndLine(string $php, int $expectedStart, int $expectedEnd): void
    {
        $reflector  = new DefaultReflector(new StringSourceLocator($php, $this->astLocator));
        $reflection = $reflector->reflectConstant('FOO');

        self::assertSame($expectedStart, $reflection->getStartLine());
        self::assertSame($expectedEnd, $reflection->getEndLine());
    }

    public function columnsProvider(): array
    {
        return [
            ["<?php\n\nconst FOO = [\n];\n", 1, 2],
            ["<?php\n\n    define(\n'FOO',\n1\n);\n", 5, 1],
        ];
    }

    /**
     * @param int $expectedStart
     * @param int $expectedEnd
     *
     * @dataProvider columnsProvider
     */
    public function testGetStartColumnAndEndColumn(string $php, int $startColumn, int $endColumn): void
    {
        $reflector  = new DefaultReflector(new StringSourceLocator($php, $this->astLocator));
        $reflection = $reflector->reflectConstant('FOO');

        self::assertSame($startColumn, $reflection->getStartColumn());
        self::assertSame($endColumn, $reflection->getEndColumn());
    }

    public function testGetAstByConst(): void
    {
        $php = '<?php const FOO = 1;';

        $reflector  = new DefaultReflector(new StringSourceLocator($php, $this->astLocator));
        $reflection = $reflector->reflectConstant('FOO');

        $ast = $reflection->getAst();

        self::assertInstanceOf(Node\Stmt\Const_::class, $ast);
        self::assertSame('FOO', $ast->consts[0]->name->name);
    }

    public function testGetAstByDefine(): void
    {
        $php = '<?php define("FOO", 1);';

        $reflector  = new DefaultReflector(new StringSourceLocator($php, $this->astLocator));
        $reflection = $reflector->reflectConstant('FOO');

        $ast = $reflection->getAst();

        self::assertInstanceOf(Node\Expr\FuncCall::class, $ast);
        self::assertSame('FOO', $ast->args[0]->value->value);
    }
}
