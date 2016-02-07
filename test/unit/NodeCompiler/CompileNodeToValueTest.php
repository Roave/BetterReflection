<?php

namespace BetterReflectionTest\NodeCompiler;

use BetterReflection\NodeCompiler\CompileNodeToValue;
use BetterReflection\NodeCompiler\CompilerContext;
use BetterReflection\NodeCompiler\Exception\UnableToCompileNode;
use BetterReflection\Reflector\ClassReflector;
use BetterReflection\SourceLocator\Type\StringSourceLocator;
use PhpParser\Lexer;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\Yield_;
use PhpParser\Node\Expr\BinaryOp\Coalesce;
use PhpParser\Node\Expr\BinaryOp\Spaceship;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\LNumber;
use PhpParser\Parser;

/**
 * @covers \BetterReflection\NodeCompiler\CompileNodeToValue
 */
class CompileNodeToValueTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param string $phpCode
     * @return \PhpParser\Node
     */
    private function parseCode($phpCode)
    {
        return (new Parser\Php7(new Lexer()))->parse('<?php ' . $phpCode . ';')[0];
    }

    private function getDummyContext()
    {
        return new CompilerContext(new ClassReflector(new StringSourceLocator('<?php')), null);
    }

    /**
     * @return array
     */
    public function nodeProvider()
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
            ['PHP_INT_MAX', PHP_INT_MAX],
            ['PHP_EOL', PHP_EOL],
        ];
    }

    /**
     * @param string $phpCode
     * @param mixed $expectedValue
     * @dataProvider nodeProvider
     */
    public function testVariousNodeCompilations($phpCode, $expectedValue)
    {
        $node = $this->parseCode($phpCode);

        $actualValue = (new CompileNodeToValue())->__invoke($node, $this->getDummyContext());

        $this->assertSame($expectedValue, $actualValue);
    }

    public function testExceptionThrownWhenInvalidNodeGiven()
    {
        $this->setExpectedException(
            UnableToCompileNode::class,
            'Unable to compile expression: ' . Yield_::class
        );
        (new CompileNodeToValue())->__invoke(new Yield_(), $this->getDummyContext());
    }

    public function testExceptionThrownWhenCoalesceOperatorUsed()
    {
        $this->setExpectedException(
            UnableToCompileNode::class,
            'Unable to compile binary operator'
        );
        (new CompileNodeToValue())->__invoke(new Coalesce(new LNumber(5), new LNumber(3)), $this->getDummyContext());
    }

    public function testExceptionThrownWhenSpaceshipOperatorUsed()
    {
        $this->setExpectedException(
            UnableToCompileNode::class,
            'Unable to compile binary operator'
        );
        (new CompileNodeToValue())->__invoke(new Spaceship(new LNumber(5), new LNumber(3)), $this->getDummyContext());
    }

    public function testExceptionThrownWhenUndefinedConstUsed()
    {
        $this->setExpectedException(
            UnableToCompileNode::class,
            'Constant "FOO" has not been defined'
        );
        (new CompileNodeToValue())->__invoke(new ConstFetch(new Name('FOO')), $this->getDummyContext());
    }

    public function testConstantValueCompiled()
    {
        $constName = uniqid('BETTER_REFLECTION_TEST_CONST_');
        define($constName, 123);

        $this->assertSame(
            123,
            (new CompileNodeToValue())->__invoke(
                new ConstFetch(new Name($constName)),
                $this->getDummyContext()
            )
        );
    }

    public function testClassConstantResolutionSelfForMethod()
    {
        $phpCode = '<?php
        class Foo {
            const BAR = "baz";
            public function method($param = self::BAR) {}
        }
        ';

        $reflector = new ClassReflector(new StringSourceLocator($phpCode));
        $classInfo = $reflector->reflect('Foo');
        $methodInfo = $classInfo->getMethod('method');
        $paramInfo = $methodInfo->getParameter('param');

        $this->assertSame('baz', $paramInfo->getDefaultValue());
    }

    public function testClassConstantResolutionExternalForMethod()
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

        $reflector = new ClassReflector(new StringSourceLocator($phpCode));
        $classInfo = $reflector->reflect('Bat');
        $methodInfo = $classInfo->getMethod('method');
        $paramInfo = $methodInfo->getParameter('param');

        $this->assertSame('baz', $paramInfo->getDefaultValue());
    }

    public function testClassConstantResolutionStaticForMethod()
    {
        $phpCode = '<?php
        class Foo {
            const BAR = "baz";
            public function method($param = static::BAR) {}
        }
        ';

        $reflector = new ClassReflector(new StringSourceLocator($phpCode));
        $classInfo = $reflector->reflect('Foo');
        $methodInfo = $classInfo->getMethod('method');
        $paramInfo = $methodInfo->getParameter('param');

        $this->assertSame('baz', $paramInfo->getDefaultValue());
    }

    public function testClassConstantClassNameResolution()
    {
        $phpCode = '<?php

        class Foo {
        }
        class Bat {
            const QUX = Foo::class;
        }
        ';

        $reflector = new ClassReflector(new StringSourceLocator($phpCode));
        $classInfo = $reflector->reflect('Bat');
        $this->assertSame('Foo', $classInfo->getConstant('QUX'));
    }

    public function testClassConstantClassNameAliasResolution()
    {
        $phpCode = '<?php
        namespace Bar;

        class Foo {
        }
        class Bat {
            const QUX = Foo::class;
        }
        ';

        $reflector = new ClassReflector(new StringSourceLocator($phpCode));
        $classInfo = $reflector->reflect('Bar\Bat');
        $this->assertSame('Bar\Foo', $classInfo->getConstant('QUX'));
    }
}
