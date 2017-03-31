<?php

namespace Roave\BetterReflectionTest\Reflection;

use Roave\BetterReflection\Reflection\Exception\InvalidAbstractFunctionNodeType;
use Roave\BetterReflection\Reflection\Exception\Uncloneable;
use Roave\BetterReflection\Reflection\ReflectionFunction;
use Roave\BetterReflection\Reflection\ReflectionFunctionAbstract;
use Roave\BetterReflection\Reflection\ReflectionParameter;
use Roave\BetterReflection\Reflection\ReflectionType;
use Roave\BetterReflection\Reflector\FunctionReflector;
use Roave\BetterReflection\SourceLocator\Located\LocatedSource;
use Roave\BetterReflection\SourceLocator\Type\ClosureSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;
use phpDocumentor\Reflection\Types\Boolean;
use phpDocumentor\Reflection\Types\Integer;
use PhpParser\Node\Expr\BinaryOp;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Scalar\LNumber;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Break_;
use PhpParser\Node\Stmt\Echo_;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Return_;
use PhpParser\PrettyPrinter\Standard as StandardPrettyPrinter;

/**
 * @covers \Roave\BetterReflection\Reflection\ReflectionFunctionAbstract
 */
class ReflectionFunctionAbstractTest extends \PHPUnit_Framework_TestCase
{
    public function testExportThrowsException()
    {
        $this->expectException(\Exception::class);
        ReflectionFunctionAbstract::export();
    }

    public function testPopulateFunctionAbstractThrowsExceptionWithInvalidNode()
    {
        $reflector = new FunctionReflector(new StringSourceLocator('<?php'));
        $locatedSource = new LocatedSource('<?php', null);

        /** @var ReflectionFunctionAbstract|\PHPUnit_Framework_MockObject_MockObject $abstract */
        $abstract = $this->getMockBuilder(ReflectionFunctionAbstract::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $breakNode = new Break_();

        $populateMethodReflection = new \ReflectionMethod(ReflectionFunctionAbstract::class, 'populateFunctionAbstract');
        $populateMethodReflection->setAccessible(true);

        $this->expectException(InvalidAbstractFunctionNodeType::class);
        $populateMethodReflection->invoke($abstract, $reflector, $breakNode, $locatedSource, null);
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

    public function testNameMethodsWithClosure()
    {
        $reflector = new FunctionReflector(new ClosureSourceLocator(function () {}));
        $functionInfo = $reflector->reflect('foo');

        $this->assertSame('Roave\BetterReflectionTest\Reflection\{closure}', $functionInfo->getName());
        $this->assertSame('Roave\BetterReflectionTest\Reflection', $functionInfo->getNamespaceName());
        $this->assertSame('{closure}', $functionInfo->getShortName());
    }

    public function testIsClosureWithRegularFunction()
    {
        $php = '<?php function foo() {}';

        $reflector = new FunctionReflector(new StringSourceLocator($php));
        $function = $reflector->reflect('foo');

        $this->assertFalse($function->isClosure());
    }

    public function testIsClosureWithClosure()
    {
        $reflector = new FunctionReflector(new ClosureSourceLocator(function () {}));
        $function = $reflector->reflect('{closure}');

        $this->assertTrue($function->isClosure());
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

        $rfaRef = new \ReflectionClass(ReflectionFunctionAbstract::class);
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
        $functionInfo = $reflector->reflect('Roave\BetterReflectionTest\Fixture\myFunction');

        $this->assertContains('Fixture/Functions.php', $functionInfo->getFileName());
    }

    public function testGetLocatedSource()
    {
        $node = new Function_('foo');
        $locatedSource = new LocatedSource('<?php function foo() {}', null);
        $reflector = new FunctionReflector(new StringSourceLocator('<?php'));
        $functionInfo = ReflectionFunction::createFromNode($reflector, $node, $locatedSource);

        $this->assertSame($locatedSource, $functionInfo->getLocatedSource());
    }

    public function testGetDocBlockReturnTypes()
    {
        $php = '<?php
            /**
             * @return bool
             */
            function foo() {}';

        $reflector = new FunctionReflector(new StringSourceLocator($php));
        $function = $reflector->reflect('foo');

        $types = $function->getDocBlockReturnTypes();

        $this->assertInternalType('array', $types);
        $this->assertCount(1, $types);
        $this->assertInstanceOf(Boolean::class, $types[0]);
    }

    public function returnTypeFunctionProvider()
    {
        return [
            ['returnsInt', 'int'],
            ['returnsString', 'string'],
            ['returnsNull', 'null'],
            ['returnsObject', \stdClass::class],
            ['returnsVoid', 'void'],
        ];
    }

    /**
     * @param string $functionToReflect
     * @param string $expectedType
     * @dataProvider returnTypeFunctionProvider
     */
    public function testGetReturnTypeWithDeclaredType($functionToReflect, $expectedType)
    {
        $reflector = new FunctionReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/Php7ReturnTypeDeclarations.php'));
        $functionInfo = $reflector->reflect($functionToReflect);

        $reflectionType = $functionInfo->getReturnType();
        $this->assertInstanceOf(ReflectionType::class, $reflectionType);
        $this->assertSame($expectedType, (string)$reflectionType);
    }

    public function testGetReturnTypeReturnsNullWhenTypeIsNotDeclared()
    {
        $reflector = new FunctionReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/Php7ReturnTypeDeclarations.php'));
        $functionInfo = $reflector->reflect('returnsNothing');
        $this->assertNull($functionInfo->getReturnType());
    }

    public function testHasReturnTypeWhenTypeDeclared()
    {
        $reflector = new FunctionReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/Php7ReturnTypeDeclarations.php'));
        $functionInfo = $reflector->reflect('returnsString');
        $this->assertTrue($functionInfo->hasReturnType());
    }

    public function testHasReturnTypeWhenTypeIsNotDeclared()
    {
        $reflector = new FunctionReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/Php7ReturnTypeDeclarations.php'));
        $functionInfo = $reflector->reflect('returnsNothing');
        $this->assertFalse($functionInfo->hasReturnType());
    }

    public function testSetReturnType()
    {
        $reflector = new FunctionReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/Php7ReturnTypeDeclarations.php'));
        $functionInfo = $reflector->reflect('returnsString');

        $functionInfo->setReturnType(new Integer());

        $this->assertSame('int', (string)$functionInfo->getReturnType());
        $this->assertStringStartsWith('function returnsString() : int', (new StandardPrettyPrinter())->prettyPrint([$functionInfo->getAst()]));
    }

    public function testRemoveReturnType()
    {
        $reflector = new FunctionReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/Php7ReturnTypeDeclarations.php'));
        $functionInfo = $reflector->reflect('returnsString');

        $functionInfo->removeReturnType();

        $this->assertNull($functionInfo->getReturnType());
        $this->assertNotContains(': string', (new StandardPrettyPrinter())->prettyPrint([$functionInfo->getAst()]));
    }

    public function testCannotClone()
    {
        $php = '<?php function foo() {}';

        $functionInfo = (new FunctionReflector(new StringSourceLocator($php)))->reflect('foo');

        $this->expectException(Uncloneable::class);
        $unused = clone $functionInfo;
    }

    public function testGetBodyAst()
    {
        $php = '<?php
            function foo() {
                echo "Hello world";
            }
        ';

        $reflector = new FunctionReflector(new StringSourceLocator($php));
        $function = $reflector->reflect('foo');

        $ast = $function->getBodyAst();

        $this->assertInternalType('array', $ast);
        $this->assertCount(1, $ast);
        $this->assertInstanceOf(Echo_::class, $ast[0]);
    }

    public function testGetBodyCode()
    {
        $php = "<?php
            function foo() {
                echo 'Hello world';
            }
        ";

        $reflector = new FunctionReflector(new StringSourceLocator($php));
        $function = $reflector->reflect('foo');

        $this->assertSame(
            "echo 'Hello world';",
            $function->getBodyCode()
        );
    }

    public function testGetAst()
    {
        $php = '<?php
            function foo() {}
        ';

        $reflector = new FunctionReflector(new StringSourceLocator($php));
        $function = $reflector->reflect('foo');

        $ast = $function->getAst();

        $this->assertInstanceOf(Function_::class, $ast);
        $this->assertSame('foo', $ast->name);
    }

    public function testSetBodyFromClosure()
    {
        $php = '<?php function foo() {}';

        $reflector = new FunctionReflector(new StringSourceLocator($php));
        $function = $reflector->reflect('foo');

        $function->setBodyFromClosure(function () {
            echo 'Hello world!';
        });

        $this->assertSame("echo 'Hello world!';", $function->getBodyCode());
    }

    public function testSetBodyFromString()
    {
        $php = '<?php function foo() {}';

        $reflector = new FunctionReflector(new StringSourceLocator($php));
        $function = $reflector->reflect('foo');

        $function->setBodyFromString("echo 'Hello world!';");

        $this->assertSame("echo 'Hello world!';", $function->getBodyCode());
    }

    public function testSetBodyFromAstWithInvalidArgumentsThrowsException()
    {
        if (version_compare(PHP_VERSION, '7.0.0') < 0) {
            $this->markTestSkipped('Only run this test on PHP 7 and above');
        }

        $php = '<?php function foo() {}';

        $reflector = new FunctionReflector(new StringSourceLocator($php));
        $function = $reflector->reflect('foo');

        $this->expectException(\TypeError::class);
        $function->setBodyFromAst([1]);
    }

    public function testSetBodyFromAst()
    {
        $php = '<?php function foo() {}';

        $reflector = new FunctionReflector(new StringSourceLocator($php));
        $function = $reflector->reflect('foo');

        $function->setBodyFromAst([
            new Echo_([
                new String_('Hello world!')
            ]),
        ]);

        $this->assertSame("echo 'Hello world!';", $function->getBodyCode());
    }

    public function testAddParameter()
    {
        $php = '<?php function foo() {}';

        $reflector = new FunctionReflector(new StringSourceLocator($php));
        $function = $reflector->reflect('foo');

        $function->addParameter('myNewParam');

        $this->assertStringStartsWith('function foo($myNewParam)', (new \PhpParser\PrettyPrinter\Standard())->prettyPrint([$function->getAst()]));
    }

    public function testRemoveParameter()
    {
        $php = '<?php function foo($a, $b) {}';

        $reflector = new FunctionReflector(new StringSourceLocator($php));
        $function = $reflector->reflect('foo');

        $function->removeParameter('a');

        $this->assertStringStartsWith('function foo($b)', (new \PhpParser\PrettyPrinter\Standard())->prettyPrint([$function->getAst()]));
    }

    public function testGetReturnStatementAstReturnsStatements()
    {
        $php = <<<'PHP'
<?php
function foo($a) {
    if ($a) {
        return 0;
    }
    return ($a + 3);
}
PHP;

        $reflector = new FunctionReflector(new StringSourceLocator($php));
        $function = $reflector->reflect('foo');

        $nodes = $function->getReturnStatementsAst();

        $this->assertCount(2, $nodes);
        $this->assertContainsOnlyInstancesOf(Return_::class, $nodes);

        reset($nodes);
        /** @var Return_ $first */
        $first = current($nodes);
        /** @var Return_ $second */
        $second = next($nodes);

        $this->assertInstanceOf(LNumber::class, $first->expr);
        $this->assertInstanceOf(BinaryOp\Plus::class, $second->expr);
    }

    public function testGetReturnStatementAstDoesNotGiveInnerScopeReturnStatements()
    {
        $php = <<<'PHP'
<?php
function foo($a) {
    $x = new class {
        public function __invoke() {
            return 5;
        }
    };
    return function () use ($x) {
        return $x();
    };
}
PHP;

        $reflector = new FunctionReflector(new StringSourceLocator($php));
        $function = $reflector->reflect('foo');

        $nodes = $function->getReturnStatementsAst();

        $this->assertCount(1, $nodes);
        $this->assertContainsOnlyInstancesOf(Return_::class, $nodes);

        reset($nodes);
        /** @var Return_ $first */
        $first = current($nodes);

        $this->assertInstanceOf(Closure::class, $first->expr);
        $this->assertSame(8, $first->getAttribute('startLine'));
        $this->assertSame(10, $first->getAttribute('endLine'));
    }
}
