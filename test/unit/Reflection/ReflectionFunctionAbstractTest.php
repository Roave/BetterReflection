<?php

namespace BetterReflectionTest\Reflection;

use BetterReflection\Reflection\ReflectionFunction;
use BetterReflection\Reflection\ReflectionParameter;
use BetterReflection\Reflector\FunctionReflector;
use BetterReflection\SourceLocator\LocatedSource;
use BetterReflection\SourceLocator\SingleFileSourceLocator;
use BetterReflection\SourceLocator\StringSourceLocator;
use PhpParser\Node\Stmt\Function_;

/**
 * @covers \BetterReflection\Reflection\ReflectionFunctionAbstract
 */
class ReflectionFunctionAbstractTest extends \PHPUnit_Framework_TestCase
{
    public function testExportThrowsException()
    {
        $this->setExpectedException(\Exception::class);
        ReflectionFunctionAbstract::export();
    }

    public function testNameMethodsWithNamespace()
    {
        $php = '<?php namespace Foo { function bar() {}}';

        $reflector = new FunctionReflector(new StringSourceLocator($php));
        $functionInfo = $reflector->reflect('Foo\bar');

        $this->assertSame('Foo\bar', $functionInfo->getName());
        $this->assertSame('Foo', $functionInfo->getNamespaceName());
        $this->assertSame('bar', $functionInfo->getShortName());
    }

    public function testNameMethodsWithoutNamespace()
    {
        $php = '<?php function foo() {}';

        $reflector = new FunctionReflector(new StringSourceLocator($php));
        $functionInfo = $reflector->reflect('foo');

        $this->assertSame('foo', $functionInfo->getName());
        $this->assertSame('', $functionInfo->getNamespaceName());
        $this->assertSame('foo', $functionInfo->getShortName());
    }

    public function testIsClosure()
    {
        $php = '<?php function foo() {}';

        $reflector = new FunctionReflector(new StringSourceLocator($php));
        $function = $reflector->reflect('foo');

        $this->assertFalse($function->isClosure());
    }

    public function testIsDeprecated()
    {
        $php = '<?php function foo() {}';

        $reflector = new FunctionReflector(new StringSourceLocator($php));
        $function = $reflector->reflect('foo');

        $this->assertFalse($function->isDeprecated());
    }

    public function testIsInternal()
    {
        $php = '<?php function foo() {}';

        $reflector = new FunctionReflector(new StringSourceLocator($php));
        $function = $reflector->reflect('foo');

        $this->assertFalse($function->isInternal());
        $this->assertTrue($function->isUserDefined());
    }

    public function variadicProvider()
    {
        return [
            ['<?php function foo($notVariadic) {}', false],
            ['<?php function foo(...$isVariadic) {}', true],
            ['<?php function foo($notVariadic, ...$isVariadic) {}', true],
        ];
    }

    /**
     * @param string $php
     * @param bool $expectingVariadic
     * @dataProvider variadicProvider
     */
    public function testIsVariadic($php, $expectingVariadic)
    {
        $reflector = new FunctionReflector(new StringSourceLocator($php));
        $function = $reflector->reflect('foo');

        $this->assertSame($expectingVariadic, $function->isVariadic());
    }

    /**
     * These generator tests were taken from nikic/php-parser - so a big thank
     * you and credit to @nikic for this (and the awesome PHP-Parser library).
     *
     * @see https://github.com/nikic/PHP-Parser/blob/1.x/test/code/parser/stmt/function/generator.test
     * @return array
     */
    public function generatorProvider()
    {
        return [
            ['<?php function foo() { return [1, 2, 3]; }', false],
            ['<?php function foo() { yield; }', true],
            ['<?php function foo() { yield $value; }', true],
            ['<?php function foo() { yield $key => $value; }', true],
            ['<?php function foo() { $data = yield; }', true],
            ['<?php function foo() { $data = (yield $value); }', true],
            ['<?php function foo() { $data = (yield $key => $value); }', true],
            ['<?php function foo() { if (yield $foo); elseif (yield $foo); }', true],
            ['<?php function foo() { if (yield $foo): elseif (yield $foo): endif; }', true],
            ['<?php function foo() { while (yield $foo); }', true],
            ['<?php function foo() { do {} while (yield $foo); }', true],
            ['<?php function foo() { switch (yield $foo) {} }', true],
            ['<?php function foo() { die(yield $foo); }', true],
            ['<?php function foo() { func(yield $foo); }', true],
            ['<?php function foo() { $foo->func(yield $foo); }', true],
            ['<?php function foo() { new Foo(yield $foo); }', true],
        ];
    }

    /**
     * @param string $php
     * @param bool $expectingGenerator
     * @dataProvider generatorProvider
     */
    public function testIsGenerator($php, $expectingGenerator)
    {
        $reflector = new FunctionReflector(new StringSourceLocator($php));
        $function = $reflector->reflect('foo');

        $this->assertSame($expectingGenerator, $function->isGenerator());
    }

    public function testIsGeneratorWhenNodeNotSet()
    {
        $php = '<?php function foo() { yield 1; }';
        $reflector = new FunctionReflector(new StringSourceLocator($php));
        $functionInfo = $reflector->reflect('foo');

        $rfaRef = new \ReflectionClass('\BetterReflection\Reflection\ReflectionFunctionAbstract');
        $rfaRefNode = $rfaRef->getProperty('node');
        $rfaRefNode->setAccessible(true);
        $rfaRefNode->setValue($functionInfo, null);

        $this->assertFalse($functionInfo->isGenerator());
    }

    public function startEndLineProvider()
    {
        return [
            ["<?php\n\nfunction foo() {\n}\n", 3, 4],
            ["<?php\n\nfunction foo() {\n\n}\n", 3, 5],
            ["<?php\n\n\nfunction foo() {\n}\n", 4, 5],
        ];
    }

    /**
     * @param string $php
     * @param int $expectedStart
     * @param int $expectedEnd
     * @dataProvider startEndLineProvider
     */
    public function testStartEndLine($php, $expectedStart, $expectedEnd)
    {
        $reflector = new FunctionReflector(new StringSourceLocator($php));
        $function = $reflector->reflect('foo');

        $this->assertSame($expectedStart, $function->getStartLine());
        $this->assertSame($expectedEnd, $function->getEndLine());
    }

    public function returnsReferenceProvider()
    {
        return [
            ['<?php function foo() {}', false],
            ['<?php function &foo() {}', true],
        ];
    }

    /**
     * @param string $php
     * @param bool $expectingReturnsReference
     * @dataProvider returnsReferenceProvider
     */
    public function testReturnsReference($php, $expectingReturnsReference)
    {
        $reflector = new FunctionReflector(new StringSourceLocator($php));
        $function = $reflector->reflect('foo');

        $this->assertSame($expectingReturnsReference, $function->returnsReference());
    }

    public function testGetDocCommentWithComment()
    {
        $php = '<?php
        /**
         * Some function comment
         */
        function foo() {}
        ';

        $reflector = new FunctionReflector(new StringSourceLocator($php));
        $functionInfo = $reflector->reflect('foo');

        $this->assertContains('Some function comment', $functionInfo->getDocComment());
    }

    public function testGetDocReturnsEmptyStringWithNoComment()
    {
        $php = '<?php function foo() {}';

        $reflector = new FunctionReflector(new StringSourceLocator($php));
        $functionInfo = $reflector->reflect('foo');

        $this->assertSame('', $functionInfo->getDocComment());
    }

    public function testGetNumberOfParameters()
    {
        $php = '<?php function foo($a, $b, $c = 1) {}';

        $reflector = new FunctionReflector(new StringSourceLocator($php));
        $functionInfo = $reflector->reflect('foo');

        $this->assertSame(3, $functionInfo->getNumberOfParameters());
        $this->assertSame(2, $functionInfo->getNumberOfRequiredParameters());
    }

    public function testGetParameter()
    {
        $php = '<?php function foo($a, $b, $c = 1) {}';

        $reflector = new FunctionReflector(new StringSourceLocator($php));
        $functionInfo = $reflector->reflect('foo');

        $paramInfo = $functionInfo->getParameter('a');

        $this->assertInstanceOf(ReflectionParameter::class, $paramInfo);
        $this->assertSame('a', $paramInfo->getName());
    }

    public function testGetParameterReturnsNullWhenNotFound()
    {
        $php = '<?php function foo($a, $b, $c = 1) {}';

        $reflector = new FunctionReflector(new StringSourceLocator($php));
        $functionInfo = $reflector->reflect('foo');

        $this->assertNull($functionInfo->getParameter('d'));
    }

    public function testGetFileName()
    {
        $reflector = new FunctionReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/Functions.php'));
        $functionInfo = $reflector->reflect('BetterReflectionTest\Fixture\myFunction');

        $this->assertContains('Fixture/Functions.php', $functionInfo->getFileName());
    }

    public function testGetLocatedSource()
    {
        $node = new Function_('foo');
        $locatedSource = new LocatedSource('<?php function foo() {}', null);
        $functionInfo = ReflectionFunction::createFromNode($node, $locatedSource);

        $this->assertSame($locatedSource, $functionInfo->getLocatedSource());
    }
}
