<?php

namespace BetterReflectionTest\NodeCompiler;

use BetterReflection\NodeCompiler\CompileNodeToValue;
use LogicException;
use PhpParser\Lexer;
use PhpParser\Node\Expr\Yield_;
use PhpParser\Parser;

class CompileNodeToValueTest extends \PHPUnit_Framework_TestCase
{
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
        ];
    }

    /**
     * @param string $phpCode
     * @param mixed $expectedValue
     * @dataProvider nodeProvider
     */
    public function testCompilations($phpCode, $expectedValue)
    {
        $node = (new Parser(new Lexer))->parse('<?php ' . $phpCode . ';');

        $actualValue = (new CompileNodeToValue())->__invoke($node[0]);

        $this->assertSame($expectedValue, $actualValue);
    }

    public function testExceptionThrownWhenInvalidNodeGiven()
    {
        $this->setExpectedException(
            LogicException::class,
            'Unable to compile expression: ' . Yield_::class
        );
        (new CompileNodeToValue())->__invoke(new Yield_());
    }
}
