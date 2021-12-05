<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection;

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
use ReflectionClass as CoreReflectionClass;
use Roave\BetterReflection\Reflection\Exception\InvalidArrowFunctionBodyNode;
use Roave\BetterReflection\Reflection\Exception\Uncloneable;
use Roave\BetterReflection\Reflection\ReflectionFunction;
use Roave\BetterReflection\Reflection\ReflectionParameter;
use Roave\BetterReflection\Reflection\ReflectionType;
use Roave\BetterReflection\Reflector\DefaultReflector;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\Located\LocatedSource;
use Roave\BetterReflection\SourceLocator\SourceStubber\ReflectionSourceStubber;
use Roave\BetterReflection\SourceLocator\Type\ClosureSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\PhpInternalSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;
use Roave\BetterReflectionTest\BetterReflectionSingleton;
use Roave\BetterReflectionTest\Fixture\Attr;
use stdClass;
use TypeError;

use function current;
use function next;
use function reset;
use function sprintf;

/**
 * @covers \Roave\BetterReflection\Reflection\ReflectionFunctionAbstract
 */
class ReflectionFunctionAbstractTest extends TestCase
{
    private Parser $parser;

    private Locator $astLocator;

    protected function setUp(): void
    {
        parent::setUp();

        $configuration    = BetterReflectionSingleton::instance();
        $this->parser     = $configuration->phpParser();
        $this->astLocator = $configuration->astLocator();
    }

    public function testNameMethodsWithNamespace(): void
    {
        $php = '<?php namespace Foo { function bar() {}}';

        $reflector    = new DefaultReflector(new StringSourceLocator($php, $this->astLocator));
        $functionInfo = $reflector->reflectFunction('Foo\bar');

        self::assertSame('Foo\bar', $functionInfo->getName());
        self::assertTrue($functionInfo->inNamespace());
        self::assertSame('Foo', $functionInfo->getNamespaceName());
        self::assertSame('bar', $functionInfo->getShortName());
    }

    public function testNameMethodsWithoutNamespace(): void
    {
        $php = '<?php function foo() {}';

        $reflector    = new DefaultReflector(new StringSourceLocator($php, $this->astLocator));
        $functionInfo = $reflector->reflectFunction('foo');

        self::assertSame('foo', $functionInfo->getName());
        self::assertFalse($functionInfo->inNamespace());
        self::assertSame('', $functionInfo->getNamespaceName());
        self::assertSame('foo', $functionInfo->getShortName());
    }

    public function testNameMethodsInRootNamespace(): void
    {
        $php = '<?php namespace { function foo() {} }';

        $reflector    = new DefaultReflector(new StringSourceLocator($php, $this->astLocator));
        $functionInfo = $reflector->reflectFunction('foo');

        self::assertSame('foo', $functionInfo->getName());
        self::assertFalse($functionInfo->inNamespace());
        self::assertSame('', $functionInfo->getNamespaceName());
        self::assertSame('foo', $functionInfo->getShortName());
    }

    public function testNameMethodsWithClosure(): void
    {
        $functionInfo = (new DefaultReflector(
            new ClosureSourceLocator(
                static function (): void {
                },
                $this->parser,
            ),
        ))->reflectFunction('foo');

        self::assertSame('Roave\BetterReflectionTest\Reflection\\' . ReflectionFunction::CLOSURE_NAME, $functionInfo->getName());
        self::assertSame('Roave\BetterReflectionTest\Reflection', $functionInfo->getNamespaceName());
        self::assertSame(ReflectionFunction::CLOSURE_NAME, $functionInfo->getShortName());
    }

    public function testIsClosureWithRegularFunction(): void
    {
        $php = '<?php function foo() {}';

        $reflector = new DefaultReflector(new StringSourceLocator($php, $this->astLocator));
        $function  = $reflector->reflectFunction('foo');

        self::assertFalse($function->isClosure());
    }

    public function testIsClosureWithClosure(): void
    {
        $function = (new DefaultReflector(
            new ClosureSourceLocator(
                static function (): void {
                },
                $this->parser,
            ),
        ))->reflectFunction(ReflectionFunction::CLOSURE_NAME);

        self::assertTrue($function->isClosure());
    }

    /**
     * @dataProvider nonDeprecatedProvider
     */
    public function testIsDeprecated(string $comment): void
    {
        $php = sprintf('<?php
        %s
        function foo() {}', $comment);

        $reflector = new DefaultReflector(new StringSourceLocator($php, $this->astLocator));
        $function  = $reflector->reflectFunction('foo');

        self::assertFalse($function->isDeprecated());
    }

    public function nonDeprecatedProvider(): array
    {
        return [
            [''],
            [
                '/**
                  * @deprecatedPolicy
                  */',
            ],
        ];
    }

    public function testIsInternal(): void
    {
        $php = '<?php function foo() {}';

        $reflector = new DefaultReflector(new StringSourceLocator($php, $this->astLocator));
        $function  = $reflector->reflectFunction('foo');

        self::assertFalse($function->isInternal());
        self::assertTrue($function->isUserDefined());
        self::assertNull($function->getExtensionName());
    }

    public function variadicProvider(): array
    {
        return [
            ['<?php function foo($notVariadic) {}', false],
            ['<?php function foo(...$isVariadic) {}', true],
            ['<?php function foo($notVariadic, ...$isVariadic) {}', true],
        ];
    }

    /**
     * @dataProvider variadicProvider
     */
    public function testIsVariadic(string $php, bool $expectingVariadic): void
    {
        $reflector = new DefaultReflector(new StringSourceLocator($php, $this->astLocator));
        $function  = $reflector->reflectFunction('foo');

        self::assertSame($expectingVariadic, $function->isVariadic());
    }

    /**
     * These generator tests were taken from nikic/php-parser - so a big thank
     * you and credit to @nikic for this (and the awesome PHP-Parser library).
     *
     * @see https://github.com/nikic/PHP-Parser/blob/1.x/test/code/parser/stmt/function/generator.test
     *
     * @return array
     */
    public function generatorProvider(): array
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
            ['<?php function foo() { yield from []; }', true],
        ];
    }

    /**
     * @dataProvider generatorProvider
     */
    public function testIsGenerator(string $php, bool $expectingGenerator): void
    {
        $reflector = new DefaultReflector(new StringSourceLocator($php, $this->astLocator));
        $function  = $reflector->reflectFunction('foo');

        self::assertSame($expectingGenerator, $function->isGenerator());
    }

    public function startEndLineProvider(): array
    {
        return [
            ["<?php\n\nfunction foo() {\n}\n", 3, 4],
            ["<?php\n\nfunction foo() {\n\n}\n", 3, 5],
            ["<?php\n\n\nfunction foo() {\n}\n", 4, 5],
        ];
    }

    /**
     * @dataProvider startEndLineProvider
     */
    public function testStartEndLine(string $php, int $expectedStart, int $expectedEnd): void
    {
        $reflector = new DefaultReflector(new StringSourceLocator($php, $this->astLocator));
        $function  = $reflector->reflectFunction('foo');

        self::assertSame($expectedStart, $function->getStartLine());
        self::assertSame($expectedEnd, $function->getEndLine());
    }

    public function columnsProvider(): array
    {
        return [
            ["<?php\n\nfunction foo() {\n}\n", 1, 1],
            ["<?php\n\n    function foo() {\n    }\n", 5, 5],
            ['<?php function foo() { }', 7, 24],
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
        $reflector = new DefaultReflector(new StringSourceLocator($php, $this->astLocator));
        $function  = $reflector->reflectFunction('foo');

        self::assertSame($startColumn, $function->getStartColumn());
        self::assertSame($endColumn, $function->getEndColumn());
    }

    public function returnsReferenceProvider(): array
    {
        return [
            ['<?php function foo() {}', false],
            ['<?php function &foo() {}', true],
        ];
    }

    /**
     * @dataProvider returnsReferenceProvider
     */
    public function testReturnsReference(string $php, bool $expectingReturnsReference): void
    {
        $reflector = new DefaultReflector(new StringSourceLocator($php, $this->astLocator));
        $function  = $reflector->reflectFunction('foo');

        self::assertSame($expectingReturnsReference, $function->returnsReference());
    }

    public function testGetDocCommentWithComment(): void
    {
        $php = '<?php
        /* --- This is a separator --------------- */

        /**
         * Unused function comment
         */
        /** This function comment should be used. */
        function foo() {}
        ';

        $reflector    = new DefaultReflector(new StringSourceLocator($php, $this->astLocator));
        $functionInfo = $reflector->reflectFunction('foo');

        self::assertStringContainsString('This function comment should be used.', $functionInfo->getDocComment());
    }

    public function testSetDocCommentFromString(): void
    {
        $php = '<?php
          function foo() {}
        ';

        $reflector = new DefaultReflector(new StringSourceLocator($php, $this->astLocator));
        $function  = $reflector->reflectFunction('foo');
        $function->setDocCommentFromString('/**  * doc comment */');

        self::assertSame('/**  * doc comment */', $function->getDocComment());
    }

    public function testGetDocReturnsEmptyStringWithNoComment(): void
    {
        $php = '<?php function foo() {}';

        $reflector    = new DefaultReflector(new StringSourceLocator($php, $this->astLocator));
        $functionInfo = $reflector->reflectFunction('foo');

        self::assertSame('', $functionInfo->getDocComment());
    }

    public function testGetNumberOfParameters(): void
    {
        $php = '<?php function foo($a, $b, $c = 1) {}';

        $reflector    = new DefaultReflector(new StringSourceLocator($php, $this->astLocator));
        $functionInfo = $reflector->reflectFunction('foo');

        self::assertSame(3, $functionInfo->getNumberOfParameters());
        self::assertSame(2, $functionInfo->getNumberOfRequiredParameters());
    }

    public function testGetParameter(): void
    {
        $php = '<?php function foo($a, $b, $c = 1) {}';

        $reflector    = new DefaultReflector(new StringSourceLocator($php, $this->astLocator));
        $functionInfo = $reflector->reflectFunction('foo');

        $paramInfo = $functionInfo->getParameter('a');

        self::assertInstanceOf(ReflectionParameter::class, $paramInfo);
        self::assertSame('a', $paramInfo->getName());
    }

    public function testGetParameterReturnsNullWhenNotFound(): void
    {
        $php = '<?php function foo($a, $b, $c = 1) {}';

        $reflector    = new DefaultReflector(new StringSourceLocator($php, $this->astLocator));
        $functionInfo = $reflector->reflectFunction('foo');

        self::assertNull($functionInfo->getParameter('d'));
    }

    public function testGetFileName(): void
    {
        $functionInfo = (new DefaultReflector(
            new SingleFileSourceLocator(__DIR__ . '/../Fixture/Functions.php', $this->astLocator),
        ))->reflectFunction('Roave\BetterReflectionTest\Fixture\myFunction');

        self::assertStringContainsString('Fixture/Functions.php', $functionInfo->getFileName());
    }

    public function testGetFileNameOfUnlocatedSource(): void
    {
        $php = '<?php function foo() {}';

        $functionInfo = (new DefaultReflector(new StringSourceLocator($php, $this->astLocator)))->reflectFunction('foo');

        self::assertNull($functionInfo->getFileName());
    }

    public function testGetLocatedSource(): void
    {
        $node          = new Function_('foo');
        $locatedSource = new LocatedSource('<?php function foo() {}', 'foo');
        $reflector     = new DefaultReflector(new StringSourceLocator('<?php', $this->astLocator));
        $functionInfo  = ReflectionFunction::createFromNode($reflector, $node, $locatedSource);

        self::assertSame($locatedSource, $functionInfo->getLocatedSource());
    }

    public function returnTypeFunctionProvider(): array
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
     * @dataProvider returnTypeFunctionProvider
     */
    public function testGetReturnTypeWithDeclaredType(string $functionToReflect, string $expectedType): void
    {
        $functionInfo = (new DefaultReflector(
            new SingleFileSourceLocator(__DIR__ . '/../Fixture/Php7ReturnTypeDeclarations.php', $this->astLocator),
        ))->reflectFunction($functionToReflect);

        $reflectionType = $functionInfo->getReturnType();
        self::assertInstanceOf(ReflectionType::class, $reflectionType);
        self::assertSame($expectedType, (string) $reflectionType);
    }

    public function testGetReturnTypeReturnsNullWhenTypeIsNotDeclared(): void
    {
        $functionInfo = (new DefaultReflector(
            new SingleFileSourceLocator(__DIR__ . '/../Fixture/Php7ReturnTypeDeclarations.php', $this->astLocator),
        ))->reflectFunction('returnsNothing');

        self::assertNull($functionInfo->getReturnType());
    }

    public function testHasReturnTypeWhenTypeDeclared(): void
    {
        $functionInfo = (new DefaultReflector(
            new SingleFileSourceLocator(__DIR__ . '/../Fixture/Php7ReturnTypeDeclarations.php', $this->astLocator),
        ))->reflectFunction('returnsString');

        self::assertTrue($functionInfo->hasReturnType());
    }

    public function testHasReturnTypeWhenTypeIsNotDeclared(): void
    {
        $functionInfo = (new DefaultReflector(
            new SingleFileSourceLocator(__DIR__ . '/../Fixture/Php7ReturnTypeDeclarations.php', $this->astLocator),
        ))->reflectFunction('returnsNothing');

        self::assertFalse($functionInfo->hasReturnType());
    }

    public function nullableReturnTypeFunctionProvider(): array
    {
        return [
            ['returnsNullableInt', '?int'],
            ['returnsNullableString', '?string'],
            ['returnsNullableObject', '?' . stdClass::class],
        ];
    }

    /**
     * @dataProvider nullableReturnTypeFunctionProvider
     */
    public function testGetNullableReturnTypeWithDeclaredType(string $functionToReflect, string $expectedType): void
    {
        $functionInfo = (new DefaultReflector(
            new SingleFileSourceLocator(__DIR__ . '/../Fixture/Php71NullableReturnTypeDeclarations.php', $this->astLocator),
        ))->reflectFunction($functionToReflect);

        $reflectionType = $functionInfo->getReturnType();
        self::assertInstanceOf(ReflectionType::class, $reflectionType);
        self::assertSame($expectedType, (string) $reflectionType);
        self::assertTrue($reflectionType->allowsNull());
    }

    public function testSetReturnType(): void
    {
        $functionInfo = (new DefaultReflector(
            new SingleFileSourceLocator(__DIR__ . '/../Fixture/Php7ReturnTypeDeclarations.php', $this->astLocator),
        ))->reflectFunction('returnsString');

        $functionInfo->setReturnType('int');

        self::assertSame('int', (string) $functionInfo->getReturnType());
        self::assertStringStartsWith('function returnsString() : int', (new StandardPrettyPrinter())->prettyPrint([$functionInfo->getAst()]));
    }

    public function testRemoveReturnType(): void
    {
        $functionInfo = (new DefaultReflector(
            new SingleFileSourceLocator(__DIR__ . '/../Fixture/Php7ReturnTypeDeclarations.php', $this->astLocator),
        ))->reflectFunction('returnsString');

        $functionInfo->removeReturnType();

        self::assertNull($functionInfo->getReturnType());
        self::assertStringNotContainsString(': string', (new StandardPrettyPrinter())->prettyPrint([$functionInfo->getAst()]));
    }

    /**
     * @requires PHP >= 8.1
     */
    public function testHasTentativeReturnType(): void
    {
        $classInfo  = (new DefaultReflector(new PhpInternalSourceLocator($this->astLocator, new ReflectionSourceStubber())))->reflectClass(CoreReflectionClass::class);
        $methodInfo = $classInfo->getMethod('getName');

        self::assertTrue($methodInfo->hasTentativeReturnType());
        self::assertFalse($methodInfo->hasReturnType());
    }

    public function testHasNotTentativeReturnType(): void
    {
        $functionInfo = (new DefaultReflector(
            new SingleFileSourceLocator(__DIR__ . '/../Fixture/Php7ReturnTypeDeclarations.php', $this->astLocator),
        ))->reflectFunction('returnsString');

        self::assertFalse($functionInfo->hasTentativeReturnType());
        self::assertTrue($functionInfo->hasReturnType());
    }

    /**
     * @requires PHP >= 8.1
     */
    public function testGetTentativeReturnType(): void
    {
        $classInfo  = (new DefaultReflector(new PhpInternalSourceLocator($this->astLocator, new ReflectionSourceStubber())))->reflectClass(CoreReflectionClass::class);
        $methodInfo = $classInfo->getMethod('getName');

        $returnType = $methodInfo->getTentativeReturnType();

        self::assertNotNull($returnType);
        self::assertSame('string', $returnType->__toString());
        self::assertNull($methodInfo->getReturnType());
    }

    public function testNoTentativeReturnType(): void
    {
        $functionInfo = (new DefaultReflector(
            new SingleFileSourceLocator(__DIR__ . '/../Fixture/Php7ReturnTypeDeclarations.php', $this->astLocator),
        ))->reflectFunction('returnsString');

        self::assertNull($functionInfo->getTentativeReturnType());
        self::assertNotNull($functionInfo->getReturnType());
    }

    public function testCannotClone(): void
    {
        $php = '<?php function foo() {}';

        $functionInfo = (new DefaultReflector(new StringSourceLocator($php, $this->astLocator)))->reflectFunction('foo');

        $this->expectException(Uncloneable::class);
        clone $functionInfo;
    }

    public function testGetBodyAst(): void
    {
        $php = '<?php
            function foo() {
                echo "Hello world";
            }
        ';

        $reflector = new DefaultReflector(new StringSourceLocator($php, $this->astLocator));
        $function  = $reflector->reflectFunction('foo');

        $ast = $function->getBodyAst();

        self::assertIsArray($ast);
        self::assertCount(1, $ast);
        self::assertInstanceOf(Echo_::class, $ast[0]);
    }

    public function testGetBodyCode(): void
    {
        $php = "<?php
            function foo() {
                echo 'Hello world';
            }
        ";

        $reflector = new DefaultReflector(new StringSourceLocator($php, $this->astLocator));
        $function  = $reflector->reflectFunction('foo');

        self::assertSame(
            "echo 'Hello world';",
            $function->getBodyCode(),
        );
    }

    public function testGetAst(): void
    {
        $php = '<?php
            function foo() {}
        ';

        $reflector = new DefaultReflector(new StringSourceLocator($php, $this->astLocator));
        $function  = $reflector->reflectFunction('foo');

        $ast = $function->getAst();

        self::assertInstanceOf(Function_::class, $ast);
        self::assertSame('foo', $ast->name->name);
    }

    public function testSetBodyFromClosure(): void
    {
        $php = '<?php function foo() {}';

        $reflector = new DefaultReflector(new StringSourceLocator($php, $this->astLocator));
        $function  = $reflector->reflectFunction('foo');

        $function->setBodyFromClosure(static function (): void {
            echo 'Hello world!';
        });

        self::assertSame("echo 'Hello world!';", $function->getBodyCode());
    }

    public function testSetBodyFromClosureWithArrowFunction(): void
    {
        $php = '<?php function foo() {}';

        $reflector = new DefaultReflector(new StringSourceLocator($php, $this->astLocator));
        $function  = $reflector->reflectFunction('foo');

        $function->setBodyFromClosure(static fn (): string => 'Hello world!');

        self::assertSame("return 'Hello world!';", $function->getBodyCode());
    }

    public function testSetBodyFromString(): void
    {
        $php = '<?php function foo() {}';

        $reflector = new DefaultReflector(new StringSourceLocator($php, $this->astLocator));
        $function  = $reflector->reflectFunction('foo');

        $function->setBodyFromString("echo 'Hello world!';");

        self::assertSame("echo 'Hello world!';", $function->getBodyCode());
    }

    public function testSetBodyFromStringForArrowFunction(): void
    {
        $arrowFunction = static fn () => 10;

        $function = ReflectionFunction::createFromClosure($arrowFunction);

        $function->setBodyFromString("'Hello world!';");

        self::assertSame("'Hello world!'", $function->getBodyCode());
    }

    public function testSetBodyFromAst(): void
    {
        $php = '<?php function foo() {}';

        $reflector = new DefaultReflector(new StringSourceLocator($php, $this->astLocator));
        $function  = $reflector->reflectFunction('foo');

        $function->setBodyFromAst([
            new Echo_([
                new String_('Hello world!'),
            ]),
        ]);

        self::assertSame("echo 'Hello world!';", $function->getBodyCode());
    }

    public function testSetBodyFromAstForArrowFunction(): void
    {
        $arrowFunction = static fn () => 10;

        $function = ReflectionFunction::createFromClosure($arrowFunction);

        $function->setBodyFromAst([
            new Return_(
                new String_('Hello world!'),
            ),
        ]);

        self::assertSame("'Hello world!'", $function->getBodyCode());
    }

    public function testSetBodyFromAstWithInvalidArgumentsThrowsException(): void
    {
        $php = '<?php function foo() {}';

        $reflector = new DefaultReflector(new StringSourceLocator($php, $this->astLocator));
        $function  = $reflector->reflectFunction('foo');

        $this->expectException(TypeError::class);
        $function->setBodyFromAst([1]);
    }

    public function testSetBodyFromAstForArrowFunctionWithInvalidArgumentsThrowsException(): void
    {
        $arrowFunction = static fn () => 10;

        $function = ReflectionFunction::createFromClosure($arrowFunction);

        $this->expectException(InvalidArrowFunctionBodyNode::class);

        $function->setBodyFromAst([
            new Echo_([
                new String_('Hello world!'),
            ]),
        ]);
    }

    public function testAddParameter(): void
    {
        $php = '<?php function foo() {}';

        $reflector = new DefaultReflector(new StringSourceLocator($php, $this->astLocator));
        $function  = $reflector->reflectFunction('foo');

        $function->addParameter('myNewParam');

        self::assertStringStartsWith('function foo($myNewParam)', (new StandardPrettyPrinter())->prettyPrint([$function->getAst()]));
    }

    public function testRemoveParameter(): void
    {
        $php = '<?php function foo($a, $b) {}';

        $reflector = new DefaultReflector(new StringSourceLocator($php, $this->astLocator));
        $function  = $reflector->reflectFunction('foo');

        $function->removeParameter('a');

        self::assertStringStartsWith('function foo($b)', (new StandardPrettyPrinter())->prettyPrint([$function->getAst()]));
    }

    public function testGetReturnStatementAstReturnsStatements(): void
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

        $reflector = new DefaultReflector(new StringSourceLocator($php, $this->astLocator));
        $function  = $reflector->reflectFunction('foo');

        $nodes = $function->getReturnStatementsAst();

        self::assertCount(2, $nodes);
        self::assertContainsOnlyInstancesOf(Return_::class, $nodes);

        reset($nodes);
        $first = current($nodes);
        self::assertInstanceOf(Return_::class, $first);
        $second = next($nodes);
        self::assertInstanceOf(Return_::class, $second);

        self::assertInstanceOf(LNumber::class, $first->expr);
        self::assertInstanceOf(BinaryOp\Plus::class, $second->expr);
    }

    public function testGetReturnStatementAstDoesNotGiveInnerScopeReturnStatements(): void
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

        $reflector = new DefaultReflector(new StringSourceLocator($php, $this->astLocator));
        $function  = $reflector->reflectFunction('foo');

        $nodes = $function->getReturnStatementsAst();

        self::assertCount(1, $nodes);
        self::assertContainsOnlyInstancesOf(Return_::class, $nodes);

        reset($nodes);
        $first = current($nodes);

        self::assertInstanceOf(Return_::class, $first);
        self::assertInstanceOf(Closure::class, $first->expr);
        self::assertSame(8, $first->getStartLine());
        self::assertSame(10, $first->getEndLine());
    }

    /**
     * @dataProvider deprecatedDocCommentsProvider
     */
    public function testFunctionsCanBeDeprecated(string $comment): void
    {
        $php = sprintf('<?php
        %s
        function foo() {}', $comment);

        $reflector = new DefaultReflector(new StringSourceLocator($php, $this->astLocator));
        $function  = $reflector->reflectFunction('foo');

        self::assertTrue($function->isDeprecated());
    }

    public function deprecatedDocCommentsProvider(): array
    {
        return [
            [
                '/**
                  * @deprecated since 7.1
                  */',
            ],
            [
                '/**
                  * @deprecated
                  */',
            ],
        ];
    }

    public function testGetAttributesWithAttributes(): void
    {
        $reflector          = new DefaultReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/Attributes.php', $this->astLocator));
        $functionReflection = $reflector->reflectFunction('Roave\BetterReflectionTest\Fixture\functionWithAttributes');
        $attributes         = $functionReflection->getAttributes();

        self::assertCount(2, $attributes);
    }

    public function testGetAttributesByName(): void
    {
        $reflector          = new DefaultReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/Attributes.php', $this->astLocator));
        $functionReflection = $reflector->reflectFunction('Roave\BetterReflectionTest\Fixture\functionWithAttributes');
        $attributes         = $functionReflection->getAttributesByName(Attr::class);

        self::assertCount(1, $attributes);
    }

    public function testGetAttributesByInstance(): void
    {
        $reflector          = new DefaultReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/Attributes.php', $this->astLocator));
        $functionReflection = $reflector->reflectFunction('Roave\BetterReflectionTest\Fixture\functionWithAttributes');
        $attributes         = $functionReflection->getAttributesByInstance(Attr::class);

        self::assertCount(2, $attributes);
    }
}
