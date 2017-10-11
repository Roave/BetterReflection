<?php
declare(strict_types=1);

namespace Rector\BetterReflectionTest\Reflection;

use Exception;
use phpDocumentor\Reflection\Types\Boolean;
use PhpParser\Node\Expr\BinaryOp;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Scalar\LNumber;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Echo_;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Return_;
use PhpParser\Parser;
use PhpParser\PrettyPrinter\Standard as StandardPrettyPrinter;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Rector\BetterReflection\Reflection\Exception\Uncloneable;
use Rector\BetterReflection\Reflection\ReflectionFunction;
use Rector\BetterReflection\Reflection\ReflectionFunctionAbstract;
use Rector\BetterReflection\Reflection\ReflectionParameter;
use Rector\BetterReflection\Reflection\ReflectionType;
use Rector\BetterReflection\Reflector\ClassReflector;
use Rector\BetterReflection\Reflector\FunctionReflector;
use Rector\BetterReflection\SourceLocator\Ast\Locator;
use Rector\BetterReflection\SourceLocator\Located\LocatedSource;
use Rector\BetterReflection\SourceLocator\Type\ClosureSourceLocator;
use Rector\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;
use Rector\BetterReflection\SourceLocator\Type\StringSourceLocator;
use Rector\BetterReflectionTest\BetterReflectionSingleton;
use stdClass;
use TypeError;

/**
 * @covers \Rector\BetterReflection\Reflection\ReflectionFunctionAbstract
 */
class ReflectionFunctionAbstractTest extends TestCase
{
    /**
     * @var Parser
     */
    private $parser;

    /**
     * @var ClassReflector
     */
    private $classReflector;

    /**
     * @var Locator
     */
    private $astLocator;

    protected function setUp() : void
    {
        parent::setUp();

        $configuration        = BetterReflectionSingleton::instance();
        $this->parser         = $configuration->phpParser();
        $this->classReflector = $configuration->classReflector();
        $this->astLocator     = $configuration->astLocator();
    }

    public function testExportThrowsException() : void
    {
        $this->expectException(Exception::class);
        ReflectionFunctionAbstract::export();
    }

    public function testNameMethodsWithNamespace() : void
    {
        $php = '<?php namespace Foo { function bar() {}}';

        $reflector    = new FunctionReflector(new StringSourceLocator($php, $this->astLocator), $this->classReflector);
        $functionInfo = $reflector->reflect('Foo\bar');

        self::assertSame('Foo\bar', $functionInfo->getName());
        self::assertSame('Foo', $functionInfo->getNamespaceName());
        self::assertSame('bar', $functionInfo->getShortName());
    }

    public function testNameMethodsWithoutNamespace() : void
    {
        $php = '<?php function foo() {}';

        $reflector    = new FunctionReflector(new StringSourceLocator($php, $this->astLocator), $this->classReflector);
        $functionInfo = $reflector->reflect('foo');

        self::assertSame('foo', $functionInfo->getName());
        self::assertSame('', $functionInfo->getNamespaceName());
        self::assertSame('foo', $functionInfo->getShortName());
    }

    public function testNameMethodsWithClosure() : void
    {
        $functionInfo = (new FunctionReflector(
            new ClosureSourceLocator(
                function () : void {
                },
                $this->parser
            ),
            $this->classReflector
        ))->reflect('foo');

        self::assertSame('Rector\BetterReflectionTest\Reflection\\' . ReflectionFunctionAbstract::CLOSURE_NAME, $functionInfo->getName());
        self::assertSame('Rector\BetterReflectionTest\Reflection', $functionInfo->getNamespaceName());
        self::assertSame(ReflectionFunctionAbstract::CLOSURE_NAME, $functionInfo->getShortName());
    }

    public function testIsClosureWithRegularFunction() : void
    {
        $php = '<?php function foo() {}';

        $reflector = new FunctionReflector(new StringSourceLocator($php, $this->astLocator), $this->classReflector);
        $function  = $reflector->reflect('foo');

        self::assertFalse($function->isClosure());
    }

    public function testIsClosureWithClosure() : void
    {
        $function = (new FunctionReflector(
            new ClosureSourceLocator(
                function () : void {
                },
                $this->parser
            ),
            $this->classReflector
        ))->reflect(ReflectionFunctionAbstract::CLOSURE_NAME);

        self::assertTrue($function->isClosure());
    }

    public function testIsDeprecated() : void
    {
        $php = '<?php function foo() {}';

        $reflector = new FunctionReflector(new StringSourceLocator($php, $this->astLocator), $this->classReflector);
        $function  = $reflector->reflect('foo');

        self::assertFalse($function->isDeprecated());
    }

    public function testIsInternal() : void
    {
        $php = '<?php function foo() {}';

        $reflector = new FunctionReflector(new StringSourceLocator($php, $this->astLocator), $this->classReflector);
        $function  = $reflector->reflect('foo');

        self::assertFalse($function->isInternal());
        self::assertTrue($function->isUserDefined());
    }

    public function variadicProvider() : array
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
    public function testIsVariadic(string $php, bool $expectingVariadic) : void
    {
        $reflector = new FunctionReflector(new StringSourceLocator($php, $this->astLocator), $this->classReflector);
        $function  = $reflector->reflect('foo');

        self::assertSame($expectingVariadic, $function->isVariadic());
    }

    /**
     * These generator tests were taken from nikic/php-parser - so a big thank
     * you and credit to @nikic for this (and the awesome PHP-Parser library).
     *
     * @see https://github.com/nikic/PHP-Parser/blob/1.x/test/code/parser/stmt/function/generator.test
     * @return array
     */
    public function generatorProvider() : array
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
    public function testIsGenerator(string $php, bool $expectingGenerator) : void
    {
        $reflector = new FunctionReflector(new StringSourceLocator($php, $this->astLocator), $this->classReflector);
        $function  = $reflector->reflect('foo');

        self::assertSame($expectingGenerator, $function->isGenerator());
    }

    public function testIsGeneratorWhenNodeNotSet() : void
    {
        $php          = '<?php function foo() { yield 1; }';
        $reflector    = new FunctionReflector(new StringSourceLocator($php, $this->astLocator), $this->classReflector);
        $functionInfo = $reflector->reflect('foo');

        $rfaRef     = new ReflectionClass(ReflectionFunctionAbstract::class);
        $rfaRefNode = $rfaRef->getProperty('node');
        $rfaRefNode->setAccessible(true);
        $rfaRefNode->setValue($functionInfo, null);

        self::assertFalse($functionInfo->isGenerator());
    }

    public function startEndLineProvider() : array
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
    public function testStartEndLine(string $php, int $expectedStart, int $expectedEnd) : void
    {
        $reflector = new FunctionReflector(new StringSourceLocator($php, $this->astLocator), $this->classReflector);
        $function  = $reflector->reflect('foo');

        self::assertSame($expectedStart, $function->getStartLine());
        self::assertSame($expectedEnd, $function->getEndLine());
    }

    public function columnsProvider() : array
    {
        return [
            ["<?php\n\nfunction foo() {\n}\n", 1, 1],
            ["<?php\n\n    function foo() {\n    }\n", 5, 5],
            ['<?php function foo() { }', 7, 24],
        ];
    }

    /**
     * @param string $php
     * @param int $expectedStart
     * @param int $expectedEnd
     * @dataProvider columnsProvider
     */
    public function testGetStartColumnAndEndColumn(string $php, int $startColumn, int $endColumn) : void
    {
        $reflector = new FunctionReflector(new StringSourceLocator($php, $this->astLocator), $this->classReflector);
        $function  = $reflector->reflect('foo');

        self::assertSame($startColumn, $function->getStartColumn());
        self::assertSame($endColumn, $function->getEndColumn());
    }

    public function returnsReferenceProvider() : array
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
    public function testReturnsReference(string $php, bool $expectingReturnsReference) : void
    {
        $reflector = new FunctionReflector(new StringSourceLocator($php, $this->astLocator), $this->classReflector);
        $function  = $reflector->reflect('foo');

        self::assertSame($expectingReturnsReference, $function->returnsReference());
    }

    public function testGetDocCommentWithComment() : void
    {
        $php = '<?php
        /* --- This is a separator --------------- */

        /**
         * Some function comment
         */
        function foo() {}
        ';

        $reflector    = new FunctionReflector(new StringSourceLocator($php, $this->astLocator), $this->classReflector);
        $functionInfo = $reflector->reflect('foo');

        self::assertContains('Some function comment', $functionInfo->getDocComment());
    }

    public function testGetDocReturnsEmptyStringWithNoComment() : void
    {
        $php = '<?php function foo() {}';

        $reflector    = new FunctionReflector(new StringSourceLocator($php, $this->astLocator), $this->classReflector);
        $functionInfo = $reflector->reflect('foo');

        self::assertSame('', $functionInfo->getDocComment());
    }

    public function testGetNumberOfParameters() : void
    {
        $php = '<?php function foo($a, $b, $c = 1) {}';

        $reflector    = new FunctionReflector(new StringSourceLocator($php, $this->astLocator), $this->classReflector);
        $functionInfo = $reflector->reflect('foo');

        self::assertSame(3, $functionInfo->getNumberOfParameters());
        self::assertSame(2, $functionInfo->getNumberOfRequiredParameters());
    }

    public function testGetParameter() : void
    {
        $php = '<?php function foo($a, $b, $c = 1) {}';

        $reflector    = new FunctionReflector(new StringSourceLocator($php, $this->astLocator), $this->classReflector);
        $functionInfo = $reflector->reflect('foo');

        $paramInfo = $functionInfo->getParameter('a');

        self::assertInstanceOf(ReflectionParameter::class, $paramInfo);
        self::assertSame('a', $paramInfo->getName());
    }

    public function testGetParameterReturnsNullWhenNotFound() : void
    {
        $php = '<?php function foo($a, $b, $c = 1) {}';

        $reflector    = new FunctionReflector(new StringSourceLocator($php, $this->astLocator), $this->classReflector);
        $functionInfo = $reflector->reflect('foo');

        self::assertNull($functionInfo->getParameter('d'));
    }

    public function testGetFileName() : void
    {
        $functionInfo = (new FunctionReflector(
            new SingleFileSourceLocator(__DIR__ . '/../Fixture/Functions.php', $this->astLocator),
            $this->classReflector
        ))->reflect('Rector\BetterReflectionTest\Fixture\myFunction');

        self::assertContains('Fixture/Functions.php', $functionInfo->getFileName());
    }

    public function testGetFileNameOfUnlocatedSource() : void
    {
        $php = '<?php function foo() {}';

        $functionInfo = (new FunctionReflector(new StringSourceLocator($php, $this->astLocator), $this->classReflector))->reflect('foo');

        self::assertNull($functionInfo->getFileName());
    }

    public function testGetLocatedSource() : void
    {
        $node          = new Function_('foo');
        $locatedSource = new LocatedSource('<?php function foo() {}', null);
        $reflector     = new FunctionReflector(new StringSourceLocator('<?php', $this->astLocator), $this->classReflector);
        $functionInfo  = ReflectionFunction::createFromNode($reflector, $node, $locatedSource);

        self::assertSame($locatedSource, $functionInfo->getLocatedSource());
    }

    public function testGetDocBlockReturnTypes() : void
    {
        $php = '<?php
            /**
             * @return bool
             */
            function foo() {}';

        $reflector = new FunctionReflector(new StringSourceLocator($php, $this->astLocator), $this->classReflector);
        $function  = $reflector->reflect('foo');

        $types = $function->getDocBlockReturnTypes();

        self::assertInternalType('array', $types);
        self::assertCount(1, $types);
        self::assertInstanceOf(Boolean::class, $types[0]);
    }

    public function returnTypeFunctionProvider() : array
    {
        return [
            ['returnsInt', 'int'],
            ['returnsString', 'string'],
            ['returnsNull', 'null'],
            ['returnsObject', stdClass::class],
            ['returnsVoid', 'void'],
        ];
    }

    /**
     * @param string $functionToReflect
     * @param string $expectedType
     * @dataProvider returnTypeFunctionProvider
     */
    public function testGetReturnTypeWithDeclaredType(string $functionToReflect, string $expectedType) : void
    {
        $functionInfo = (new FunctionReflector(
            new SingleFileSourceLocator(__DIR__ . '/../Fixture/Php7ReturnTypeDeclarations.php', $this->astLocator),
            $this->classReflector
        ))->reflect($functionToReflect);

        $reflectionType = $functionInfo->getReturnType();
        self::assertInstanceOf(ReflectionType::class, $reflectionType);
        self::assertSame($expectedType, (string) $reflectionType);
    }

    public function testGetReturnTypeReturnsNullWhenTypeIsNotDeclared() : void
    {
        $functionInfo = (new FunctionReflector(
            new SingleFileSourceLocator(__DIR__ . '/../Fixture/Php7ReturnTypeDeclarations.php', $this->astLocator),
            $this->classReflector
        ))->reflect('returnsNothing');

        self::assertNull($functionInfo->getReturnType());
    }

    public function testHasReturnTypeWhenTypeDeclared() : void
    {
        $functionInfo = (new FunctionReflector(
            new SingleFileSourceLocator(__DIR__ . '/../Fixture/Php7ReturnTypeDeclarations.php', $this->astLocator),
            $this->classReflector
        ))->reflect('returnsString');

        self::assertTrue($functionInfo->hasReturnType());
    }

    public function testHasReturnTypeWhenTypeIsNotDeclared() : void
    {
        $functionInfo = (new FunctionReflector(
            new SingleFileSourceLocator(__DIR__ . '/../Fixture/Php7ReturnTypeDeclarations.php', $this->astLocator),
            $this->classReflector
        ))->reflect('returnsNothing');

        self::assertFalse($functionInfo->hasReturnType());
    }

    public function nullableReturnTypeFunctionProvider() : array
    {
        return [
            ['returnsNullableInt', 'int'],
            ['returnsNullableString', 'string'],
            ['returnsNullableObject', stdClass::class],
        ];
    }

    /**
     * @param string $functionToReflect
     * @param string $expectedType
     * @dataProvider nullableReturnTypeFunctionProvider
     */
    public function testGetNullableReturnTypeWithDeclaredType(string $functionToReflect, string $expectedType) : void
    {
        $functionInfo = (new FunctionReflector(
            new SingleFileSourceLocator(__DIR__ . '/../Fixture/Php71NullableReturnTypeDeclarations.php', $this->astLocator),
            $this->classReflector
        ))->reflect($functionToReflect);

        $reflectionType = $functionInfo->getReturnType();
        self::assertInstanceOf(ReflectionType::class, $reflectionType);
        self::assertSame($expectedType, (string) $reflectionType);
        self::assertTrue($reflectionType->allowsNull());
    }

    public function testSetReturnType() : void
    {
        $functionInfo = (new FunctionReflector(
            new SingleFileSourceLocator(__DIR__ . '/../Fixture/Php7ReturnTypeDeclarations.php', $this->astLocator),
            $this->classReflector
        ))->reflect('returnsString');

        $functionInfo->setReturnType('int');

        self::assertSame('int', (string) $functionInfo->getReturnType());
        self::assertStringStartsWith('function returnsString() : int', (new StandardPrettyPrinter())->prettyPrint([$functionInfo->getAst()]));
    }

    public function testRemoveReturnType() : void
    {
        $functionInfo = (new FunctionReflector(
            new SingleFileSourceLocator(__DIR__ . '/../Fixture/Php7ReturnTypeDeclarations.php', $this->astLocator),
            $this->classReflector
        ))->reflect('returnsString');

        $functionInfo->removeReturnType();

        self::assertNull($functionInfo->getReturnType());
        self::assertNotContains(': string', (new StandardPrettyPrinter())->prettyPrint([$functionInfo->getAst()]));
    }

    public function testCannotClone() : void
    {
        $php = '<?php function foo() {}';

        $functionInfo = (new FunctionReflector(new StringSourceLocator($php, $this->astLocator), $this->classReflector))->reflect('foo');

        $this->expectException(Uncloneable::class);
        $unused = clone $functionInfo;
    }

    public function testGetBodyAst() : void
    {
        $php = '<?php
            function foo() {
                echo "Hello world";
            }
        ';

        $reflector = new FunctionReflector(new StringSourceLocator($php, $this->astLocator), $this->classReflector);
        $function  = $reflector->reflect('foo');

        $ast = $function->getBodyAst();

        self::assertInternalType('array', $ast);
        self::assertCount(1, $ast);
        self::assertInstanceOf(Echo_::class, $ast[0]);
    }

    public function testGetBodyCode() : void
    {
        $php = "<?php
            function foo() {
                echo 'Hello world';
            }
        ";

        $reflector = new FunctionReflector(new StringSourceLocator($php, $this->astLocator), $this->classReflector);
        $function  = $reflector->reflect('foo');

        self::assertSame(
            "echo 'Hello world';",
            $function->getBodyCode()
        );
    }

    public function testGetAst() : void
    {
        $php = '<?php
            function foo() {}
        ';

        $reflector = new FunctionReflector(new StringSourceLocator($php, $this->astLocator), $this->classReflector);
        $function  = $reflector->reflect('foo');

        $ast = $function->getAst();

        self::assertInstanceOf(Function_::class, $ast);
        self::assertSame('foo', $ast->name);
    }

    public function testSetBodyFromClosure() : void
    {
        $php = '<?php function foo() {}';

        $reflector = new FunctionReflector(new StringSourceLocator($php, $this->astLocator), $this->classReflector);
        $function  = $reflector->reflect('foo');

        $function->setBodyFromClosure(function () : void {
            echo 'Hello world!';
        });

        self::assertSame("echo 'Hello world!';", $function->getBodyCode());
    }

    public function testSetBodyFromString() : void
    {
        $php = '<?php function foo() {}';

        $reflector = new FunctionReflector(new StringSourceLocator($php, $this->astLocator), $this->classReflector);
        $function  = $reflector->reflect('foo');

        $function->setBodyFromString("echo 'Hello world!';");

        self::assertSame("echo 'Hello world!';", $function->getBodyCode());
    }

    public function testSetBodyFromAstWithInvalidArgumentsThrowsException() : void
    {
        $php = '<?php function foo() {}';

        $reflector = new FunctionReflector(new StringSourceLocator($php, $this->astLocator), $this->classReflector);
        $function  = $reflector->reflect('foo');

        $this->expectException(TypeError::class);
        $function->setBodyFromAst([1]);
    }

    public function testSetBodyFromAst() : void
    {
        $php = '<?php function foo() {}';

        $reflector = new FunctionReflector(new StringSourceLocator($php, $this->astLocator), $this->classReflector);
        $function  = $reflector->reflect('foo');

        $function->setBodyFromAst([
            new Echo_([
                new String_('Hello world!'),
            ]),
        ]);

        self::assertSame("echo 'Hello world!';", $function->getBodyCode());
    }

    public function testAddParameter() : void
    {
        $php = '<?php function foo() {}';

        $reflector = new FunctionReflector(new StringSourceLocator($php, $this->astLocator), $this->classReflector);
        $function  = $reflector->reflect('foo');

        $function->addParameter('myNewParam');

        self::assertStringStartsWith('function foo($myNewParam)', (new StandardPrettyPrinter())->prettyPrint([$function->getAst()]));
    }

    public function testRemoveParameter() : void
    {
        $php = '<?php function foo($a, $b) {}';

        $reflector = new FunctionReflector(new StringSourceLocator($php, $this->astLocator), $this->classReflector);
        $function  = $reflector->reflect('foo');

        $function->removeParameter('a');

        self::assertStringStartsWith('function foo($b)', (new StandardPrettyPrinter())->prettyPrint([$function->getAst()]));
    }

    public function testGetReturnStatementAstReturnsStatements() : void
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

        $reflector = new FunctionReflector(new StringSourceLocator($php, $this->astLocator), $this->classReflector);
        $function  = $reflector->reflect('foo');

        $nodes = $function->getReturnStatementsAst();

        self::assertCount(2, $nodes);
        self::assertContainsOnlyInstancesOf(Return_::class, $nodes);

        \reset($nodes);
        /** @var Return_ $first */
        $first = \current($nodes);
        /** @var Return_ $second */
        $second = \next($nodes);

        self::assertInstanceOf(LNumber::class, $first->expr);
        self::assertInstanceOf(BinaryOp\Plus::class, $second->expr);
    }

    public function testGetReturnStatementAstDoesNotGiveInnerScopeReturnStatements() : void
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

        $reflector = new FunctionReflector(new StringSourceLocator($php, $this->astLocator), $this->classReflector);
        $function  = $reflector->reflect('foo');

        $nodes = $function->getReturnStatementsAst();

        self::assertCount(1, $nodes);
        self::assertContainsOnlyInstancesOf(Return_::class, $nodes);

        \reset($nodes);
        /** @var Return_ $first */
        $first = \current($nodes);

        self::assertInstanceOf(Closure::class, $first->expr);
        self::assertSame(8, $first->getAttribute('startLine'));
        self::assertSame(10, $first->getAttribute('endLine'));
    }
}
