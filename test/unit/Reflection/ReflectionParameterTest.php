<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection;

use InvalidArgumentException;
use LogicException;
use OutOfBoundsException;
use PhpParser\Node;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\Reflection\Exception\CodeLocationMissing;
use Roave\BetterReflection\Reflection\ReflectionFunction;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use Roave\BetterReflection\Reflection\ReflectionParameter;
use Roave\BetterReflection\Reflector\DefaultReflector;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\ComposerSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;
use Roave\BetterReflectionTest\BetterReflectionSingleton;
use Roave\BetterReflectionTest\Fixture\Attr;
use Roave\BetterReflectionTest\Fixture\ClassForHinting;
use Roave\BetterReflectionTest\Fixture\ClassWithAttributes;
use Roave\BetterReflectionTest\Fixture\ClassWithConstantsAsDefaultValues;
use Roave\BetterReflectionTest\Fixture\ExampleClass;
use Roave\BetterReflectionTest\Fixture\Methods;
use Roave\BetterReflectionTest\Fixture\NullableParameterTypeDeclarations;
use Roave\BetterReflectionTest\Fixture\PhpParameterTypeDeclarations;
use Roave\BetterReflectionTest\Fixture\StringEnum;
use Roave\BetterReflectionTest\FixtureOther\OtherClass;
use SplDoublyLinkedList;
use stdClass;
use Throwable;

use function sprintf;

use const SORT_ASC as SORT_ASC_TEST;

#[CoversClass(ReflectionParameter::class)]
class ReflectionParameterTest extends TestCase
{
    private Reflector $reflector;

    private Locator $astLocator;

    public function setUp(): void
    {
        parent::setUp();

        $betterReflection = BetterReflectionSingleton::instance();

        $this->astLocator = $betterReflection->astLocator();
        $this->reflector  = new DefaultReflector(new ComposerSourceLocator($GLOBALS['loader'], $this->astLocator));
    }

    public function testCreateFromClassNameAndMethod(): void
    {
        $parameterInfo = ReflectionParameter::createFromClassNameAndMethod(SplDoublyLinkedList::class, 'add', 'index');

        self::assertInstanceOf(ReflectionParameter::class, $parameterInfo);
        self::assertSame('index', $parameterInfo->getName());
    }

    public function testCreateFromClassNameAndMethodThrowsExceptionWhenParameterDoesNotExist(): void
    {
        $this->expectException(OutOfBoundsException::class);
        ReflectionParameter::createFromClassNameAndMethod(SplDoublyLinkedList::class, 'add', 'notExist');
    }

    public function testCreateFromClassInstanceAndMethod(): void
    {
        $parameterInfo = ReflectionParameter::createFromClassInstanceAndMethod(new SplDoublyLinkedList(), 'add', 'index');

        self::assertInstanceOf(ReflectionParameter::class, $parameterInfo);
        self::assertSame('index', $parameterInfo->getName());
    }

    public function testCreateFromClassInstanceAndMethodThrowsExceptionWhenParameterDoesNotExist(): void
    {
        $this->expectException(OutOfBoundsException::class);
        ReflectionParameter::createFromClassInstanceAndMethod(new SplDoublyLinkedList(), 'add', 'notExist');
    }

    public function testCreateFromCallable(): void
    {
        $parameterInfo = ReflectionParameter::createFromClosure(static function ($a): void {
        }, 'a');

        self::assertInstanceOf(ReflectionParameter::class, $parameterInfo);
        self::assertSame('a', $parameterInfo->getName());
    }

    public function testParamWithConstant(): void
    {
        $parameterInfo = ReflectionParameter::createFromClosure(static function (int $sort = SORT_ASC_TEST): void {
        }, 'sort');

        self::assertInstanceOf(ReflectionParameter::class, $parameterInfo);
        self::assertFalse($parameterInfo->allowsNull());
    }

    public function testParamWithConstantAlias(): void
    {
        $parameterInfo = ReflectionParameter::createFromClosure(static function (int $sort = SORT_ASC_TEST): void {
        }, 'sort');

        self::assertInstanceOf(ReflectionParameter::class, $parameterInfo);
        self::assertFalse($parameterInfo->allowsNull());
    }

    public function testCreateFromClosureThrowsExceptionWhenParameterDoesNotExist(): void
    {
        $this->expectException(OutOfBoundsException::class);
        $this->expectExceptionMessage('Could not find parameter: notExist');
        ReflectionParameter::createFromClosure(static function ($a): void {
        }, 'notExist');
    }

    public function testCreateFromSpecWithArray(): void
    {
        $parameterInfo = ReflectionParameter::createFromSpec([SplDoublyLinkedList::class, 'add'], 'index');

        self::assertInstanceOf(ReflectionParameter::class, $parameterInfo);
        self::assertSame('index', $parameterInfo->getName());
    }

    public function testCreateFromSpecWithArrayWithInstance(): void
    {
        $splDoublyLinkedList = new SplDoublyLinkedList();
        $parameterInfo       = ReflectionParameter::createFromSpec([$splDoublyLinkedList, 'add'], 'index');

        self::assertInstanceOf(ReflectionParameter::class, $parameterInfo);
        self::assertSame('index', $parameterInfo->getName());
    }

    public function testCreateFromSpecWithFunctionName(): void
    {
        require_once __DIR__ . '/../Fixture/ClassForHinting.php';
        $parameterInfo = ReflectionParameter::createFromSpec('Roave\BetterReflectionTest\Fixture\testFunction', 'param1');

        self::assertInstanceOf(ReflectionParameter::class, $parameterInfo);
        self::assertSame('param1', $parameterInfo->getName());
    }

    public function testCreateFromSpecWithFunctionNameThrowsExceptionWhenParameterDoesNotExist(): void
    {
        require_once __DIR__ . '/../Fixture/ClassForHinting.php';

        try {
            ReflectionParameter::createFromSpec('Roave\BetterReflectionTest\Fixture\testFunction', 'notExists');
            self::fail('Parameter should not exits');
        } catch (Throwable $e) {
            self::assertInstanceOf(InvalidArgumentException::class, $e);
            self::assertInstanceOf(OutOfBoundsException::class, $e->getPrevious());
        }
    }

    public function testCreateFromSpecWithClosure(): void
    {
        $parameterInfo = ReflectionParameter::createFromSpec(static function ($a): void {
        }, 'a');

        self::assertInstanceOf(ReflectionParameter::class, $parameterInfo);
        self::assertSame('a', $parameterInfo->getName());
    }

    public function testCreateFromSpecWithInvalidSpecThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        ReflectionParameter::createFromSpec([], 'index');
    }

    /** @return list<array{0: string, 1: mixed}> */
    public static function defaultParameterProvider(): array
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

    #[DataProvider('defaultParameterProvider')]
    public function testDefaultParametersTypes(string $defaultExpression, mixed $expectedValue): void
    {
        $content = sprintf('<?php class Foo { public function myMethod($var = %s) {} }', $defaultExpression);

        $reflector  = new DefaultReflector(new StringSourceLocator($content, $this->astLocator));
        $classInfo  = $reflector->reflectClass('Foo');
        $methodInfo = $classInfo->getMethod('myMethod');
        $paramInfo  = $methodInfo->getParameter('var');

        self::assertInstanceOf(Node\Expr::class, $paramInfo->getDefaultValueExpression());
        self::assertSame($expectedValue, $paramInfo->getDefaultValue());
    }

    public function testGetDefaultValueWhenDefaultValueNotAvailableThrowsException(): void
    {
        $content = '<?php class Foo { public function myMethod($var) {} }';

        $reflector  = new DefaultReflector(new StringSourceLocator($content, $this->astLocator));
        $classInfo  = $reflector->reflectClass('Foo');
        $methodInfo = $classInfo->getMethod('myMethod');
        $paramInfo  = $methodInfo->getParameter('var');

        self::assertFalse($paramInfo->isDefaultValueAvailable());
        self::assertNull($paramInfo->getDefaultValueExpression());

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('This parameter does not have a default value available');
        $paramInfo->getDefaultValue();
    }

    public function testToString(): void
    {
        $classInfo = $this->reflector->reflectClass(Methods::class);
        $method    = $classInfo->getMethod('methodWithOptionalParameters');

        $requiredParam = $method->getParameter('parameter');
        self::assertSame('Parameter #0 [ <required> $parameter ]', (string) $requiredParam);

        $optionalParam = $method->getParameter('optionalParameter');
        self::assertSame('Parameter #1 [ <optional> $optionalParameter = NULL ]', (string) $optionalParam);
    }

    public function testGetPosition(): void
    {
        $classInfo = $this->reflector->reflectClass(Methods::class);

        $method = $classInfo->getMethod('methodWithParameters');

        $param1 = $method->getParameter('parameter1');
        self::assertSame(0, $param1->getPosition());

        $param2 = $method->getParameter('parameter2');
        self::assertSame(1, $param2->getPosition());
    }

    /** @return list<array{0: non-empty-string, 1: string}> */
    public static function typeProvider(): array
    {
        return [
            ['stdClassParameter', 'stdClass'],
            ['fullyQualifiedClassParameter', ClassForHinting::class],
            ['arrayParameter', 'array'],
            ['callableParameter', 'callable'],
            ['namespaceClassParameter', ClassForHinting::class],
        ];
    }

    /** @param non-empty-string $parameterToTest */
    #[DataProvider('typeProvider')]
    public function testGetType(
        string $parameterToTest,
        string $expectedType,
    ): void {
        $classInfo = $this->reflector->reflectClass(Methods::class);

        $method = $classInfo->getMethod('methodWithExplicitTypedParameters');

        $type = $method->getParameter($parameterToTest)->getType();

        self::assertSame($expectedType, (string) $type);
    }

    public function testPhp7TypeDeclarationWithIntBuiltinType(): void
    {
        $classInfo = $this->reflector->reflectClass(PhpParameterTypeDeclarations::class);
        $method    = $classInfo->getMethod('foo');

        $intParamType = $method->getParameter('intParam')->getType();
        self::assertSame('int', (string) $intParamType);
        self::assertTrue($intParamType->isBuiltin());
        self::assertFalse($intParamType->allowsNull());
    }

    public function testPhp7TypeDeclarationWithClassTypeIsNotBuiltin(): void
    {
        $classInfo = $this->reflector->reflectClass(PhpParameterTypeDeclarations::class);
        $method    = $classInfo->getMethod('foo');

        $classParamType = $method->getParameter('classParam')->getType();
        self::assertSame(stdClass::class, (string) $classParamType);
        self::assertFalse($classParamType->isBuiltin());
        self::assertFalse($classParamType->allowsNull());
    }

    public function testPhp7TypeDeclarationWithoutType(): void
    {
        $classInfo = $this->reflector->reflectClass(PhpParameterTypeDeclarations::class);
        $method    = $classInfo->getMethod('foo');

        self::assertNull($method->getParameter('noTypeParam')->getType());
    }

    /** @return list<array{0: non-empty-string, 1: bool}> */
    public static function allowsNullProvider(): array
    {
        return [
            ['classParam', false],
            ['noTypeParam', true],
            ['nullableStringAllowsNull', true],
            ['unionWithNullOnFirstPositionAllowsNull', true],
            ['unionWithNullOnLastPositionAllowsNull', true],
            ['stringParamWithNullDefaultValueAllowsNull', true],
            ['stringWithNullConstantDefaultValueDoesNotAllowNull', false],
        ];
    }

    /** @param non-empty-string $parameterName */
    #[DataProvider('allowsNullProvider')]
    public function testAllowsNull(string $parameterName, bool $allowsNull): void
    {
        $classInfo = $this->reflector->reflectClass(NullableParameterTypeDeclarations::class);
        $method    = $classInfo->getMethod('foo');
        $parameter = $method->getParameter($parameterName);

        self::assertSame($allowsNull, $parameter->allowsNull());
    }

    public function testHasTypeReturnsTrueWithType(): void
    {
        $classInfo = $this->reflector->reflectClass(PhpParameterTypeDeclarations::class);
        $method    = $classInfo->getMethod('foo');

        self::assertTrue($method->getParameter('intParam')->hasType());
    }

    public function testHasTypeReturnsFalseWithoutType(): void
    {
        $classInfo = $this->reflector->reflectClass(PhpParameterTypeDeclarations::class);
        $method    = $classInfo->getMethod('foo');

        self::assertFalse($method->getParameter('noTypeParam')->hasType());
    }

    public function testIsVariadic(): void
    {
        $classInfo = $this->reflector->reflectClass(Methods::class);

        $method = $classInfo->getMethod('methodWithVariadic');

        $nonVariadicParam = $method->getParameter('nonVariadicParameter');
        self::assertFalse($nonVariadicParam->isVariadic());

        $variadicParam = $method->getParameter('variadicParameter');
        self::assertTrue($variadicParam->isVariadic());
    }

    public function testIsPassedByReference(): void
    {
        $classInfo = $this->reflector->reflectClass(Methods::class);

        $method = $classInfo->getMethod('methodWithReference');

        $nonRefParam = $method->getParameter('nonRefParameter');
        self::assertFalse($nonRefParam->isPassedByReference());
        self::assertTrue($nonRefParam->canBePassedByValue());

        $refParam = $method->getParameter('refParameter');
        self::assertTrue($refParam->isPassedByReference());
        self::assertFalse($refParam->canBePassedByValue());
    }

    public function testIsPromoted(): void
    {
        $classInfo = $this->reflector->reflectClass(Methods::class);

        $constructor = $classInfo->getConstructor();

        $promoted = $constructor->getParameter('promotedParameter');
        self::assertTrue($promoted->isPromoted());

        $notPromoted = $constructor->getParameter('notPromotedParameter');
        self::assertFalse($notPromoted->isPromoted());
    }

    public function testGetDefaultValueAndIsOptional(): void
    {
        $classInfo = $this->reflector->reflectClass(Methods::class);
        $method    = $classInfo->getMethod('methodWithNonOptionalDefaultValue');

        $firstParam = $method->getParameter('firstParameter');
        self::assertFalse($firstParam->isOptional());
        self::assertTrue($firstParam->isDefaultValueAvailable());

        $secondParam = $method->getParameter('secondParameter');
        self::assertFalse($secondParam->isOptional());
        self::assertFalse($secondParam->isDefaultValueAvailable());
    }

    public function testParameterWithDefaultValueBeforeVariadicParameterShouldBeOptional(): void
    {
        $classInfo = $this->reflector->reflectClass(Methods::class);
        $method    = $classInfo->getMethod('methodWithFirstParameterWithDefaultValueAndSecondParameterIsVariadic');

        $firstParam = $method->getParameter('parameterWithDefaultValue');
        self::assertTrue($firstParam->isOptional());
        self::assertTrue($firstParam->isDefaultValueAvailable());

        $secondParam = $method->getParameter('variadicParameter');
        self::assertTrue($secondParam->isOptional());
        self::assertTrue($secondParam->isVariadic());
    }

    /** @group 109 */
    public function testVariadicParametersAreAlsoImplicitlyOptional(): void
    {
        $classInfo = $this->reflector->reflectClass(Methods::class);

        $method = $classInfo->getMethod('methodWithVariadic');

        $nonVariadicParam = $method->getParameter('nonVariadicParameter');
        self::assertFalse($nonVariadicParam->isVariadic());
        self::assertFalse($nonVariadicParam->isOptional());

        $variadicParam = $method->getParameter('variadicParameter');
        self::assertTrue($variadicParam->isVariadic());
        self::assertTrue($variadicParam->isOptional());
    }

    public function testIsDefaultValueConstantAndGetDefaultValueConstantName(): void
    {
        $reflector = new DefaultReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/Methods.php',
            $this->astLocator,
        ));

        $classInfo = $reflector->reflectClass(Methods::class);
        $method    = $classInfo->getMethod('methodWithUpperCasedDefaults');

        $boolUpper = $method->getParameter('boolUpper');
        self::assertFalse($boolUpper->isDefaultValueConstant());

        $boolLower = $method->getParameter('boolLower');
        self::assertFalse($boolLower->isDefaultValueConstant());

        $nullUpper = $method->getParameter('nullUpper');
        self::assertFalse($nullUpper->isDefaultValueConstant());

        $method       = $classInfo->getMethod('methodWithConstAsDefault');
        $constDefault = $method->getParameter('constDefault');
        self::assertTrue($constDefault->isDefaultValueConstant());
        self::assertSame(Methods::class . '::SOME_CONST', $constDefault->getDefaultValueConstantName());

        $definedDefault = $method->getParameter('definedDefault');
        self::assertTrue($definedDefault->isDefaultValueConstant());
        self::assertSame('SOME_DEFINED_VALUE', $definedDefault->getDefaultValueConstantName());

        $intDefault = $method->getParameter('intDefault');
        self::assertFalse($intDefault->isDefaultValueConstant());

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('This parameter is not a constant default value, so cannot have a constant name');
        $intDefault->getDefaultValueConstantName();
    }

    public function testGetDefaultValueConstantNameClassConstants(): void
    {
        $reflector = new DefaultReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/ClassWithConstantsAsDefaultValues.php',
            $this->astLocator,
        ));
        $classInfo = $reflector->reflectClass(ClassWithConstantsAsDefaultValues::class);
        $method    = $classInfo->getMethod('method');

        $param1 = $method->getParameter('param1');
        self::assertSame(ClassWithConstantsAsDefaultValues::class . '::MY_CONST', $param1->getDefaultValueConstantName());

        $param2 = $method->getParameter('param2');
        self::assertSame(ClassWithConstantsAsDefaultValues::class . '::PARENT_CONST', $param2->getDefaultValueConstantName());

        $param3 = $method->getParameter('param3');
        self::assertSame(OtherClass::class . '::MY_CONST', $param3->getDefaultValueConstantName());

        $param6 = $method->getParameter('param6');
        self::assertSame(ClassWithConstantsAsDefaultValues::class . '::class', $param6->getDefaultValueConstantName());

        $methodFromTrait = $classInfo->getMethod('methodFromTrait');

        $param1 = $methodFromTrait->getParameter('param1');
        self::assertSame(ClassWithConstantsAsDefaultValues::class . '::MY_CONST', $param1->getDefaultValueConstantName());
    }

    public function testGetDefaultValueConstantNameNamespacedConstants(): void
    {
        $reflector = new DefaultReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/ClassWithConstantsAsDefaultValues.php',
            $this->astLocator,
        ));
        $classInfo = $reflector->reflectClass(ClassWithConstantsAsDefaultValues::class);
        $method    = $classInfo->getMethod('method');

        $param4 = $method->getParameter('param4');
        self::assertSame('Roave\BetterReflectionTest\Fixture\THIS_NAMESPACE_CONST', $param4->getDefaultValueConstantName());

        $param5 = $method->getParameter('param5');
        self::assertSame('Roave\BetterReflectionTest\FixtureOther\OTHER_NAMESPACE_CONST', $param5->getDefaultValueConstantName());

        $param7 = $method->getParameter('param7');
        self::assertSame('GLOBAL_CONST', $param7->getDefaultValueConstantName());

        $param8 = $method->getParameter('param8');
        self::assertSame('Roave\BetterReflectionTest\Fixture\UNSURE_CONST', $param8->getDefaultValueConstantName());
        self::assertSame('this', $param8->getDefaultValue());
    }

    public function testGetDeclaringFunction(): void
    {
        $content = '<?php class Foo { public function myMethod($var = 123) {} }';

        $reflector  = new DefaultReflector(new StringSourceLocator($content, $this->astLocator));
        $classInfo  = $reflector->reflectClass('Foo');
        $methodInfo = $classInfo->getMethod('myMethod');
        $paramInfo  = $methodInfo->getParameter('var');

        self::assertSame($methodInfo, $paramInfo->getDeclaringFunction());
    }

    public function testGetDeclaringClassForMethod(): void
    {
        $content = '<?php class Foo { public function myMethod($var = 123) {} }';

        $reflector  = new DefaultReflector(new StringSourceLocator($content, $this->astLocator));
        $classInfo  = $reflector->reflectClass('Foo');
        $methodInfo = $classInfo->getMethod('myMethod');
        $paramInfo  = $methodInfo->getParameter('var');

        self::assertSame($classInfo, $paramInfo->getDeclaringClass());
    }

    public function testGetDeclaringAndImplementingClassForFunctionReturnsNull(): void
    {
        $content = '<?php function myMethod($var = 123) {}';

        $reflector    = new DefaultReflector(new StringSourceLocator($content, $this->astLocator));
        $functionInfo = $reflector->reflectFunction('myMethod');
        $paramInfo    = $functionInfo->getParameter('var');

        self::assertNull($paramInfo->getDeclaringClass());
        self::assertNull($paramInfo->getImplementingClass());
    }

    /** @return list<array{0: non-empty-string, 1: int, 2: int, 3: int, 4: int}> */
    public static function linesAndColumnsProvider(): array
    {
        return [
            ["<?php\n\nfunction foo(\n\$test\n) {}", 4, 4, 1, 5],
            ["<?php\n\n    function foo(\n    &\$test) {    \n    }\n", 4, 4, 5, 10],
            ['<?php function foo(...$test) { }', 1, 1, 20, 27],
            ["<?php function foo(array \$test\n=\nnull) { }", 1, 3, 20, 4],
        ];
    }

    /** @param non-empty-string $php */
    #[DataProvider('linesAndColumnsProvider')]
    public function testGetLinesAndColumns(string $php, int $startLine, int $endLine, int $startColumn, int $endColumn): void
    {
        $reflector = new DefaultReflector(new StringSourceLocator($php, $this->astLocator));
        $function  = $reflector->reflectFunction('foo');
        $parameter = $function->getParameter('test');

        self::assertSame($startLine, $parameter->getStartLine());
        self::assertSame($endLine, $parameter->getEndLine());
        self::assertSame($startColumn, $parameter->getStartColumn());
        self::assertSame($endColumn, $parameter->getEndColumn());
    }

    public function testGetStartLineThrowsExceptionWhenMissing(): void
    {
        $reflector          = $this->createMock(Reflector::class);
        $parameterNode      = new Node\Param(new Node\Expr\Variable('foo'));
        $functionReflection = $this->createMock(ReflectionFunction::class);

        $parameterReflection = ReflectionParameter::createFromNode(
            $reflector,
            $parameterNode,
            $functionReflection,
            0,
            false,
        );

        $this->expectException(CodeLocationMissing::class);
        $parameterReflection->getStartLine();
    }

    public function testGetEndLineThrowsExceptionWhenMissing(): void
    {
        $reflector          = $this->createMock(Reflector::class);
        $parameterNode      = new Node\Param(new Node\Expr\Variable('foo'));
        $functionReflection = $this->createMock(ReflectionFunction::class);

        $parameterReflection = ReflectionParameter::createFromNode(
            $reflector,
            $parameterNode,
            $functionReflection,
            0,
            false,
        );

        $this->expectException(CodeLocationMissing::class);
        $parameterReflection->getEndLine();
    }

    public function testGetStartColumnThrowsExceptionWhenMissing(): void
    {
        $reflector          = $this->createMock(Reflector::class);
        $parameterNode      = new Node\Param(new Node\Expr\Variable('foo'));
        $functionReflection = $this->createMock(ReflectionFunction::class);

        $parameterReflection = ReflectionParameter::createFromNode(
            $reflector,
            $parameterNode,
            $functionReflection,
            0,
            false,
        );

        $this->expectException(CodeLocationMissing::class);
        $parameterReflection->getStartColumn();
    }

    public function testGetEndColumnThrowsExceptionWhenMissing(): void
    {
        $reflector          = $this->createMock(Reflector::class);
        $parameterNode      = new Node\Param(new Node\Expr\Variable('foo'));
        $functionReflection = $this->createMock(ReflectionFunction::class);

        $parameterReflection = ReflectionParameter::createFromNode(
            $reflector,
            $parameterNode,
            $functionReflection,
            0,
            false,
        );

        $this->expectException(CodeLocationMissing::class);
        $parameterReflection->getEndColumn();
    }

    public function testGetStartLineThrowsExceptionForMagicallyAddedEnumMethod(): void
    {
        $reflector = new DefaultReflector(new AggregateSourceLocator([
            new SingleFileSourceLocator(__DIR__ . '/../Fixture/Enums.php', $this->astLocator),
            BetterReflectionSingleton::instance()->sourceLocator(),
        ]));

        $classReflection     = $reflector->reflectClass(StringEnum::class);
        $methodReflection    = $classReflection->getMethod('tryFrom');
        $parameterReflection = $methodReflection->getParameter('value');

        $this->expectException(CodeLocationMissing::class);
        $parameterReflection->getStartLine();
    }

    public function testGetEndLineThrowsExceptionForMagicallyAddedEnumMethod(): void
    {
        $reflector = new DefaultReflector(new AggregateSourceLocator([
            new SingleFileSourceLocator(__DIR__ . '/../Fixture/Enums.php', $this->astLocator),
            BetterReflectionSingleton::instance()->sourceLocator(),
        ]));

        $classReflection     = $reflector->reflectClass(StringEnum::class);
        $methodReflection    = $classReflection->getMethod('tryFrom');
        $parameterReflection = $methodReflection->getParameter('value');

        $this->expectException(CodeLocationMissing::class);
        $parameterReflection->getEndLine();
    }

    public function testGetStartColumnThrowsExceptionForMagicallyAddedEnumMethod(): void
    {
        $reflector = new DefaultReflector(new AggregateSourceLocator([
            new SingleFileSourceLocator(__DIR__ . '/../Fixture/Enums.php', $this->astLocator),
            BetterReflectionSingleton::instance()->sourceLocator(),
        ]));

        $classReflection     = $reflector->reflectClass(StringEnum::class);
        $methodReflection    = $classReflection->getMethod('tryFrom');
        $parameterReflection = $methodReflection->getParameter('value');

        $this->expectException(CodeLocationMissing::class);
        $parameterReflection->getStartColumn();
    }

    public function testGetEndColumnThrowsExceptionForMagicallyAddedEnumMethod(): void
    {
        $reflector = new DefaultReflector(new AggregateSourceLocator([
            new SingleFileSourceLocator(__DIR__ . '/../Fixture/Enums.php', $this->astLocator),
            BetterReflectionSingleton::instance()->sourceLocator(),
        ]));

        $classReflection     = $reflector->reflectClass(StringEnum::class);
        $methodReflection    = $classReflection->getMethod('tryFrom');
        $parameterReflection = $methodReflection->getParameter('value');

        $this->expectException(CodeLocationMissing::class);
        $parameterReflection->getEndColumn();
    }

    public function testGetAttributesWithoutAttributes(): void
    {
        $reflector        = new DefaultReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/ExampleClass.php', $this->astLocator));
        $classReflection  = $reflector->reflectClass(ExampleClass::class);
        $methodReflection = $classReflection->getMethod('__construct');
        $attributes       = $methodReflection->getAttributes();

        self::assertCount(0, $attributes);
    }

    public function testGetAttributesWithAttributes(): void
    {
        $reflector           = new DefaultReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/Attributes.php', $this->astLocator));
        $classReflection     = $reflector->reflectClass(ClassWithAttributes::class);
        $methodReflection    = $classReflection->getMethod('methodWithAttributes');
        $parameterReflection = $methodReflection->getParameter('parameterWithAttributes');
        $attributes          = $parameterReflection->getAttributes();

        self::assertCount(2, $attributes);
    }

    public function testGetAttributesByName(): void
    {
        $reflector           = new DefaultReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/Attributes.php', $this->astLocator));
        $classReflection     = $reflector->reflectClass(ClassWithAttributes::class);
        $methodReflection    = $classReflection->getMethod('methodWithAttributes');
        $parameterReflection = $methodReflection->getParameter('parameterWithAttributes');
        $attributes          = $parameterReflection->getAttributesByName(Attr::class);

        self::assertCount(1, $attributes);
    }

    public function testGetAttributesByInstance(): void
    {
        $reflector           = new DefaultReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/Attributes.php', $this->astLocator));
        $classReflection     = $reflector->reflectClass(ClassWithAttributes::class);
        $methodReflection    = $classReflection->getMethod('methodWithAttributes');
        $parameterReflection = $methodReflection->getParameter('parameterWithAttributes');
        $attributes          = $parameterReflection->getAttributesByInstance(Attr::class);

        self::assertCount(2, $attributes);
    }

    public function testWithFunction(): void
    {
        $reflector           = new DefaultReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/Attributes.php', $this->astLocator));
        $classReflection     = $reflector->reflectClass(ClassWithAttributes::class);
        $methodReflection    = $classReflection->getMethod('methodWithAttributes');
        $parameterReflection = $methodReflection->getParameter('parameterWithAttributes');
        $attributes          = $parameterReflection->getAttributes();

        self::assertCount(2, $attributes);

        $functionReflection = $this->createMock(ReflectionMethod::class);

        $cloneParameterReflection = $parameterReflection->withFunction($functionReflection);

        self::assertNotSame($parameterReflection, $cloneParameterReflection);
        self::assertNotSame($parameterReflection->getDeclaringFunction(), $cloneParameterReflection->getDeclaringFunction());
        self::assertNotSame($parameterReflection->getType(), $cloneParameterReflection->getType());

        $cloneAttributes = $cloneParameterReflection->getAttributes();

        self::assertCount(2, $cloneAttributes);
        self::assertNotSame($attributes[0], $cloneAttributes[0]);
    }
}
