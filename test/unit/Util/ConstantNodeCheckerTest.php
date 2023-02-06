<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Util;

use PhpParser\Node;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\Reflection\Exception\InvalidConstantNode;
use Roave\BetterReflection\Util\ConstantNodeChecker;

#[CoversClass(ConstantNodeChecker::class)]
class ConstantNodeCheckerTest extends TestCase
{
    public function testWithoutName(): void
    {
        $node = new Node\Expr\FuncCall(new Node\Expr\Variable('foo'));

        $this->expectException(InvalidConstantNode::class);
        $this->expectExceptionMessage('Invalid constant node (first 50 characters: $foo())');

        ConstantNodeChecker::assertValidDefineFunctionCall($node);
    }

    public function testDifferentName(): void
    {
        $node = new Node\Expr\FuncCall(new Node\Name('foo'), [new Node\Arg(new Node\Scalar\String_('FOO')), new Node\Arg(new Node\Scalar\LNumber(1))]);

        $this->expectException(InvalidConstantNode::class);
        $this->expectExceptionMessage("Invalid constant node (first 50 characters: foo('FOO', 1))");

        ConstantNodeChecker::assertValidDefineFunctionCall($node);
    }

    public function testLessArguments(): void
    {
        $node = new Node\Expr\FuncCall(new Node\Name('define'), [new Node\Arg(new Node\Scalar\String_('FOO'))]);

        $this->expectException(InvalidConstantNode::class);
        $this->expectExceptionMessage("Invalid constant node (first 50 characters: define('FOO'))");

        ConstantNodeChecker::assertValidDefineFunctionCall($node);
    }

    public function testMoreArguments(): void
    {
        $node = new Node\Expr\FuncCall(new Node\Name('define'), [
            new Node\Arg(new Node\Scalar\String_('FOO1')),
            new Node\Arg(new Node\Scalar\String_('FOO2')),
            new Node\Arg(new Node\Scalar\String_('FOO3')),
            new Node\Arg(new Node\Scalar\String_('FOO4')),
        ]);

        $this->expectException(InvalidConstantNode::class);
        $this->expectExceptionMessage("Invalid constant node (first 50 characters: define('FOO1', 'FOO2', 'FOO3', 'FOO4'))");

        ConstantNodeChecker::assertValidDefineFunctionCall($node);
    }

    public function testInvalidWithVariadicPlaceholderAsName(): void
    {
        $node = new Node\Expr\FuncCall(new Node\Name('define'), [new Node\VariadicPlaceholder()]);

        $this->expectException(InvalidConstantNode::class);
        $this->expectExceptionMessage('Invalid constant node (first 50 characters: define(...))');

        ConstantNodeChecker::assertValidDefineFunctionCall($node);
    }

    public function testInvalidWithVariadicPlaceholderAsValue(): void
    {
        $node = new Node\Expr\FuncCall(new Node\Name('define'), [new Node\Arg(new Node\Scalar\String_('FOO')), new Node\VariadicPlaceholder()]);

        $this->expectException(InvalidConstantNode::class);
        $this->expectExceptionMessage("Invalid constant node (first 50 characters: define('FOO', ...))");

        ConstantNodeChecker::assertValidDefineFunctionCall($node);
    }

    public function testNameAsNotString(): void
    {
        $node = new Node\Expr\FuncCall(new Node\Name('define'), [new Node\Arg(new Node\Expr\Variable('FOO')), new Node\Arg(new Node\Scalar\String_('foo'))]);

        $this->expectException(InvalidConstantNode::class);
        $this->expectExceptionMessage('Invalid constant node (first 50 characters: define($FOO, \'foo\'))');

        ConstantNodeChecker::assertValidDefineFunctionCall($node);
    }

    public function testValueAsFunctionCall(): void
    {
        $node = new Node\Expr\FuncCall(new Node\Name('define'), [new Node\Arg(new Node\Scalar\String_('FOO')), new Node\Arg(new Node\Expr\FuncCall(new Node\Name('fopen')))]);

        $this->expectException(InvalidConstantNode::class);
        $this->expectExceptionMessage("Invalid constant node (first 50 characters: define('FOO', fopen()))");

        ConstantNodeChecker::assertValidDefineFunctionCall($node);
    }

    public function testValueAsVariable(): void
    {
        $node = new Node\Expr\FuncCall(new Node\Name('define'), [new Node\Arg(new Node\Scalar\String_('FOO')), new Node\Arg(new Node\Expr\Variable('foo'))]);

        $this->expectException(InvalidConstantNode::class);
        $this->expectExceptionMessage('Invalid constant node (first 50 characters: define(\'FOO\', $foo))');

        ConstantNodeChecker::assertValidDefineFunctionCall($node);
    }

    /** @return list<array{0: Node\Expr}> */
    public static function validValuesProvider(): array
    {
        return [
            [new Node\Scalar\String_('foo')],
            [new Node\Scalar\LNumber(1)],
            [new Node\Scalar\DNumber(1.0)],
            [new Node\Expr\UnaryMinus(new Node\Scalar\LNumber(1))],
            [new Node\Expr\ConstFetch(new Node\Name('true'))],
            [new Node\Expr\ConstFetch(new Node\Name('false'))],
            [new Node\Expr\ConstFetch(new Node\Name('null'))],
            [new Node\Scalar\MagicConst\Dir()],
            [new Node\Expr\BinaryOp\BitwiseAnd(new Node\Scalar\LNumber(1), new Node\Scalar\LNumber(2))],
            [new Node\Expr\BinaryOp\BitwiseOr(new Node\Scalar\LNumber(1), new Node\Scalar\LNumber(2))],
            [new Node\Expr\AssignOp\Concat(new Node\Scalar\String_('foo'), new Node\Scalar\String_('boo'))],
            [new Node\Expr\FuncCall(new Node\Name('constant'), [new Node\Arg(new Node\Scalar\String_('STDIN'))])],
        ];
    }

    #[DataProvider('validValuesProvider')]
    public function testValidValues(Node\Expr $valueNode): void
    {
        self::expectNotToPerformAssertions();

        $node = new Node\Expr\FuncCall(new Node\Name('define'), [new Node\Arg(new Node\Scalar\String_('FOO')), new Node\Arg($valueNode)]);

        ConstantNodeChecker::assertValidDefineFunctionCall($node);
    }
}
