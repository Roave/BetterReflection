<?php

namespace BetterReflectionTest\NodeCompiler;

use BetterReflection\NodeCompiler\CompileNodeToValue;
use BetterReflection\NodeCompiler\Exception\UnableToCompileNode;
use PhpParser\Lexer;
use PhpParser\Node\Expr\Yield_;
use PhpParser\Parser;

class CompileNodeToValueTest extends \PHPUnit_Framework_TestCase
{
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
        ];
    }

    /**
     * @param string $phpCode
     * @param mixed $expectedValue
     * @dataProvider nodeProvider
     */
    public function testCompilations($phpCode, $expectedValue)
    {
        $node = (new Parser\Php7(new Lexer()))->parse('<?php ' . $phpCode . ';');

        $actualValue = (new CompileNodeToValue())->__invoke($node[0]);

        $this->assertSame($expectedValue, $actualValue);
    }

    public function testExceptionThrownWhenInvalidNodeGiven()
    {
        $this->setExpectedException(
            UnableToCompileNode::class,
            'Unable to compile expression: ' . Yield_::class
        );
        (new CompileNodeToValue())->__invoke(new Yield_());
    }
}
