<?php
declare(strict_types=1);

namespace Roave\BetterReflectionTest\NodeCompiler;

use PhpParser\Node;
use PhpParser\Node\Expr\BinaryOp\Coalesce;
use PhpParser\Node\Expr\BinaryOp\Spaceship;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\Yield_;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\LNumber;
use PhpParser\Parser;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\NodeCompiler\CompileNodeToValue;
use Roave\BetterReflection\NodeCompiler\CompilerContext;
use Roave\BetterReflection\NodeCompiler\Exception\UnableToCompileNode;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;
use Roave\BetterReflectionTest\BetterReflectionSingleton;

/**
 * @covers \Roave\BetterReflection\NodeCompiler\CompileNodeToValue
 */
class CompileNodeToValueTest extends TestCase
{
    /**
     * @var Parser
     */
    private $parser;

    /**
     * @var Locator
     */
    private $astLocator;

    protected function setUp() : void
    {
        parent::setUp();

        $configuration    = BetterReflectionSingleton::instance();
        $this->parser     = $configuration->phpParser();
        $this->astLocator = $configuration->astLocator();
    }

    /**
     * @param string $phpCode
     * @return \PhpParser\Node
     */
    private function parseCode(string $phpCode) : Node
    {
        return $this->parser->parse('<?php ' . $phpCode . ';')[0];
    }

    private function getDummyContext() : CompilerContext
    {
        return new CompilerContext(new ClassReflector(new StringSourceLocator('<?php', $this->astLocator)), null);
    }

    public function nodeProvider() : array
    {
        return [
            ['1', 1],
            ['"hello"', 'hello'],
            ['null', null],
            ['1.1', 1.1],
            ['[]', []],
            ['false', false],
            ['true', true],
            ['[1,2,3]', [1, 2, 3]],
            ['["foo","bar"]', ['foo', 'bar']],
            ['[1 => "foo", 2 => "bar"]', [1 => 'foo', 2 => 'bar']],
            ['["foo" => "bar"]', ['foo' => 'bar']],
            ['-1', -1],
            ['-123.456', -123.456],
            ['2 * 3', 6],
            ['2 + 2 * 3', 8],
            ['2 + (2 * 3)', 8],
            ['(2 + 2) * 3', 12],
            ['5 - 2', 3],
            ['8 / 2', 4],
            ['["foo"."bar" => 2 * 3]', ['foobar' => 6]],
            ['true && false', false],
            ['false && true', false],
            ['false && false', false],
            ['true && true', true],
            ['true || false', true],
            ['false || true', true],
            ['false || false', false],
            ['true || true', true],
            ['0 & 2', 0],
            ['1 & 2', 0],
            ['2 & 2', 2],
            ['3 & 2', 2],
            ['4 & 2', 0],
            ['0 | 2', 2],
            ['1 | 2', 3],
            ['2 | 2', 2],
            ['3 | 2', 3],
            ['4 | 2', 6],
            ['0 ^ 2', 2],
            ['1 ^ 2', 3],
            ['2 ^ 2', 0],
            ['3 ^ 2', 1],
            ['4 ^ 2', 6],
            ['1 == 2', false],
            ['2 == 2', true],
            ['1 == "1"', true],
            ['1 > 2', false],
            ['2 > 2', false],
            ['3 > 2', true],
            ['1 >= 2', false],
            ['2 >= 2', true],
            ['3 >= 2', true],
            ['1 === 2', false],
            ['2 === 2', true],
            ['1 === "1"', false],
            ['true and false', false],
            ['false and true', false],
            ['false and false', false],
            ['true and true', true],
            ['true or false', true],
            ['false or true', true],
            ['false or false', false],
            ['true or true', true],
            ['true xor false', true],
            ['false xor true', true],
            ['false xor false', false],
            ['true xor true', false],
            ['2 % 2', 0],
            ['2 % 4', 2],
            ['1 != 2', true],
            ['2 != 2', false],
            ['1 != "1"', false],
            ['1 !== 2', true],
            ['2 !== 2', false],
            ['1 !== "1"', true],
            ['4 ** 3', 64],
            ['1 << 1', 2],
            ['1 << 2', 4],
            ['1 << 3', 8],
            ['2 >> 1', 1],
            ['4 >> 2', 1],
            ['8 >> 3', 1],
            ['1 < 2', true],
            ['2 < 2', false],
            ['3 < 2', false],
            ['1 <= 2', true],
            ['2 <= 2', true],
            ['3 <= 2', false],
            ['PHP_INT_MAX', \PHP_INT_MAX],
            ['PHP_EOL', \PHP_EOL],
        ];
    }

    /**
     * @param string $phpCode
     * @param mixed $expectedValue
     * @dataProvider nodeProvider
     */
    public function testVariousNodeCompilations(string $phpCode, $expectedValue) : void
    {
        $node = $this->parseCode($phpCode);

        $actualValue = (new CompileNodeToValue())->__invoke($node, $this->getDummyContext());

        self::assertSame($expectedValue, $actualValue);
    }

    public function testExceptionThrownWhenInvalidNodeGiven() : void
    {
        $this->expectException(UnableToCompileNode::class);
        $this->expectExceptionMessage('Unable to compile expression: ' . Yield_::class);
        (new CompileNodeToValue())->__invoke(new Yield_(), $this->getDummyContext());
    }

    public function testExceptionThrownWhenCoalesceOperatorUsed() : void
    {
        $this->expectException(UnableToCompileNode::class);
        $this->expectExceptionMessage('Unable to compile binary operator');
        (new CompileNodeToValue())->__invoke(new Coalesce(new LNumber(5), new LNumber(3)), $this->getDummyContext());
    }

    public function testExceptionThrownWhenSpaceshipOperatorUsed() : void
    {
        $this->expectException(UnableToCompileNode::class);
        $this->expectExceptionMessage('Unable to compile binary operator');
        (new CompileNodeToValue())->__invoke(new Spaceship(new LNumber(5), new LNumber(3)), $this->getDummyContext());
    }

    public function testExceptionThrownWhenUndefinedConstUsed() : void
    {
        $this->expectException(UnableToCompileNode::class);
        $this->expectExceptionMessage('Constant "FOO" has not been defined');
        (new CompileNodeToValue())->__invoke(new ConstFetch(new Name('FOO')), $this->getDummyContext());
    }

    public function testConstantValueCompiled() : void
    {
        $constName = \uniqid('BETTER_REFLECTION_TEST_CONST_', true);
        \define($constName, 123);

        self::assertSame(
            123,
            (new CompileNodeToValue())->__invoke(
                new ConstFetch(new Name($constName)),
                $this->getDummyContext()
            )
        );
    }

    public function testClassConstantResolutionSelfForMethod() : void
    {
        $phpCode = '<?php
        class Foo {
            const BAR = "baz";
            public function method($param = self::BAR) {}
        }
        ';

        $reflector  = new ClassReflector(new StringSourceLocator($phpCode, $this->astLocator));
        $classInfo  = $reflector->reflect('Foo');
        $methodInfo = $classInfo->getMethod('method');
        $paramInfo  = $methodInfo->getParameter('param');

        self::assertSame('baz', $paramInfo->getDefaultValue());
    }

    public function testClassConstantResolutionExternalForMethod() : void
    {
        $phpCode = '<?php
        class Foo {
            const BAR = "baz";
        }
        class Bat {
            const QUX = "quux";
            public function method($param = Foo::BAR) {}
        }
        ';

        $reflector  = new ClassReflector(new StringSourceLocator($phpCode, $this->astLocator));
        $classInfo  = $reflector->reflect('Bat');
        $methodInfo = $classInfo->getMethod('method');
        $paramInfo  = $methodInfo->getParameter('param');

        self::assertSame('baz', $paramInfo->getDefaultValue());
    }

    public function testClassConstantResolutionStaticForMethod() : void
    {
        $phpCode = '<?php
        class Foo {
            const BAR = "baz";
            public function method($param = static::BAR) {}
        }
        ';

        $reflector  = new ClassReflector(new StringSourceLocator($phpCode, $this->astLocator));
        $classInfo  = $reflector->reflect('Foo');
        $methodInfo = $classInfo->getMethod('method');
        $paramInfo  = $methodInfo->getParameter('param');

        self::assertSame('baz', $paramInfo->getDefaultValue());
    }

    public function testClassConstantClassNameResolution() : void
    {
        $phpCode = '<?php

        class Foo {
        }
        class Bat {
            const QUX = Foo::class;
        }
        ';

        $reflector = new ClassReflector(new StringSourceLocator($phpCode, $this->astLocator));
        $classInfo = $reflector->reflect('Bat');
        self::assertSame('Foo', $classInfo->getConstant('QUX'));
    }

    public function testClassConstantClassNameNamespaceResolution() : void
    {
        $phpCode = '<?php
        namespace Bar;

        class Foo {
        }
        class Bat {
            const QUX = Foo::class;
        }
        ';

        $reflector = new ClassReflector(new StringSourceLocator($phpCode, $this->astLocator));
        $classInfo = $reflector->reflect('Bar\Bat');
        self::assertSame('Bar\Foo', $classInfo->getConstant('QUX'));
    }

    public function testClassConstantClassNameOutOfScopeResolution() : void
    {
        $phpCode = '<?php
        namespace Bar;

        use My\Awesome\Foo;

        class Bat {
            const QUX = Foo::class;
        }
        ';

        $reflector = new ClassReflector(new StringSourceLocator($phpCode, $this->astLocator));
        $classInfo = $reflector->reflect('Bar\Bat');
        self::assertSame('My\Awesome\Foo', $classInfo->getConstant('QUX'));
    }

    public function testClassConstantClassNameAliasedResolution() : void
    {
        $phpCode = '<?php
        namespace Bar;

        use My\Awesome\Foo as FooAlias;

        class Bat {
            const QUX = FooAlias::class;
        }
        ';

        $reflector = new ClassReflector(new StringSourceLocator($phpCode, $this->astLocator));
        $classInfo = $reflector->reflect('Bar\Bat');
        self::assertSame('My\Awesome\Foo', $classInfo->getConstant('QUX'));
    }

    public function testClassConstantResolutionFromParent() : void
    {
        $phpCode = '<?php
        namespace Bar;

        class Foo {
            const BAR = "baz";
        }
        class Bat extends Foo {
            private $property = self::BAR;
        }
        ';

        $reflector = new ClassReflector(new StringSourceLocator($phpCode, $this->astLocator));
        $classInfo = $reflector->reflect('Bar\Bat');
        self::assertSame('baz', $classInfo->getProperty('property')->getDefaultValue());
    }


    public function testClassConstantResolutionFromParentParent() : void
    {
        $phpCode = '<?php
        namespace Bar;

        class Foo {
            const BAR = "baz";
        }
        class Bar extends Foo {}
        class Bat extends Bar {
            private $property = self::BAR;
        }
        ';

        $reflector = new ClassReflector(new StringSourceLocator($phpCode, $this->astLocator));
        $classInfo = $reflector->reflect('Bar\Bat');
        self::assertSame('baz', $classInfo->getProperty('property')->getDefaultValue());
    }

    public function testDifferentClassConstantAsDefaultValueWhenInNamespace() : void
    {
        $phpCode = '<?php
        namespace Foo;

        class Foo {
            const BAR = "baz";
        }

        class Bar {
            private $property = Foo::BAR;
        }
        ';

        $reflector = new ClassReflector(new StringSourceLocator($phpCode, $this->astLocator));
        $classInfo = $reflector->reflect('Foo\Bar');
        self::assertSame('baz', $classInfo->getProperty('property')->getDefaultValue());
    }

    public function testDifferentClassConstantAsDefaultValueWhenNotInNamespace() : void
    {
        $phpCode = '<?php
        class Foo {
            const BAR = "baz";
        }

        class Bar {
            private $property = Foo::BAR;
        }
        ';

        $reflector = new ClassReflector(new StringSourceLocator($phpCode, $this->astLocator));
        $classInfo = $reflector->reflect('Bar');
        self::assertSame('baz', $classInfo->getProperty('property')->getDefaultValue());
    }
}
