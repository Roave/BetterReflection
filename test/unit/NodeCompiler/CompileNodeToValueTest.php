<?php

namespace BetterReflectionTest\NodeCompiler;

use BetterReflection\NodeCompiler\CompileNodeToValue;
use BetterReflection\NodeCompiler\Exception\UnableToCompileNode;
use BetterReflection\Reflector\ClassReflector;
use BetterReflection\SourceLocator\StringSourceLocator;
use PhpParser\Lexer;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\Yield_;
use PhpParser\Node\Expr\BinaryOp\Coalesce;
use PhpParser\Node\Expr\BinaryOp\Spaceship;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\LNumber;
use PhpParser\Parser;

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

    private function getDummyReflector()
    {
        return new ClassReflector(new StringSourceLocator('<?php'));
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

        $actualValue = (new CompileNodeToValue())->__invoke($node, $this->getDummyReflector());

        $this->assertSame($expectedValue, $actualValue);
    }

    public function testExceptionThrownWhenInvalidNodeGiven()
    {
        $this->setExpectedException(
            UnableToCompileNode::class,
            'Unable to compile expression: ' . Yield_::class
        );
        (new CompileNodeToValue())->__invoke(new Yield_(), $this->getDummyReflector());
    }

    public function testExceptionThrownWhenCoalesceOperatorUsed()
    {
        $this->setExpectedException(
            UnableToCompileNode::class,
            'Unable to compile binary operator'
        );
        (new CompileNodeToValue())->__invoke(new Coalesce(new LNumber(5), new LNumber(3)), $this->getDummyReflector());
    }

    public function testExceptionThrownWhenSpaceshipOperatorUsed()
    {
        $this->setExpectedException(
            UnableToCompileNode::class,
            'Unable to compile binary operator'
        );
        (new CompileNodeToValue())->__invoke(new Spaceship(new LNumber(5), new LNumber(3)), $this->getDummyReflector());
    }

    public function testExceptionThrownWhenConstUsed()
    {
        $this->setExpectedException(
            UnableToCompileNode::class,
            'Unable to compile constant expressions'
        );
        (new CompileNodeToValue())->__invoke(new ConstFetch(new Name('FOO')), $this->getDummyReflector());
    }

    public function testClassConstantResolutionForMethod()
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
}
