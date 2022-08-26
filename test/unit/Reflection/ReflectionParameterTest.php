<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection;

use Foo;
use InvalidArgumentException;
use LogicException;
use OutOfBoundsException;
use PhpParser\Node\Param;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\Reflection\Exception\Uncloneable;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionParameter;
use Roave\BetterReflection\Reflector\DefaultReflector;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\SourceStubber\SourceStubber;
use Roave\BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\ComposerSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\PhpInternalSourceLocator;
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
use Roave\BetterReflectionTest\FixtureOther\OtherClass;
use SplDoublyLinkedList;
use stdClass;

use function sprintf;

use const SORT_ASC as SORT_ASC_TEST;

/** @covers \Roave\BetterReflection\Reflection\ReflectionParameter */
class ReflectionParameterTest extends TestCase
{
    private Reflector $reflector;

    private Locator $astLocator;

    private SourceStubber $sourceStubber;

    public function setUp(): void
    {
        parent::setUp();

        $betterReflection = BetterReflectionSingleton::instance();

        $this->astLocator    = $betterReflection->astLocator();
        $this->sourceStubber = $betterReflection->sourceStubber();
        $this->reflector     = new DefaultReflector(new ComposerSourceLocator($GLOBALS['loader'], $this->astLocator));
    }

    public function testCreateFromClassNameAndMethod(): void
    {
        $parameterInfo = ReflectionParameter::createFromClassNameAndMethod(SplDoublyLinkedList::class, 'add', 'index');

        self::assertInstanceOf(ReflectionParameter::class, $parameterInfo);
        self::assertSame('index', $parameterInfo->getName());
    }

    public function testCreateFromClassNameAndMethodThrowsExceptionWhenParameterDoesNotExist(): void
    {
        self::expectException(OutOfBoundsException::class);
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
        self::expectException(OutOfBoundsException::class);
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
        self::expectException(OutOfBoundsException::class);
        self::expectExceptionMessage('Could not find parameter: notExist');
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

        self::expectException(InvalidArgumentException::class);
        ReflectionParameter::createFromSpec('Roave\BetterReflectionTest\Fixture\testFunction', 'notExists');
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
        self::expectException(InvalidArgumentException::class);
        ReflectionParameter::createFromSpec([], 'index');
    }

    /** @return list<array{0: string, 1: mixed}> */
    public function defaultParameterProvider(): array
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

    /** @dataProvider defaultParameterProvider */
    public function testDefaultParametersTypes(string $defaultExpression, mixed $expectedValue): void
    {
        $content = sprintf('<?php class Foo { public function myMethod($var = %s) {} }', $defaultExpression);

        $reflector   = new DefaultReflector(new StringSourceLocator($content, $this->astLocator));
        $classInfo   = $reflector->reflectClass('Foo');
        $methodInfo  = $classInfo->getMethod('myMethod');
        $paramInfo   = $methodInfo->getParameter('var');
        $actualValue = $paramInfo->getDefaultValue();

        self::assertSame($expectedValue, $actualValue);
    }

    public function testGetDefaultValueWhenDefaultValueNotAvailableThrowsException(): void
    {
        $content = '<?php class Foo { public function myMethod($var) {} }';

        $reflector  = new DefaultReflector(new StringSourceLocator($content, $this->astLocator));
        $classInfo  = $reflector->reflectClass('Foo');
        $methodInfo = $classInfo->getMethod('myMethod');
        $paramInfo  = $methodInfo->getParameter('var');

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

    /** @return list<array{0: string, 1: string}> */
    public function typeProvider(): array
    {
        return [
            ['stdClassParameter', 'stdClass'],
            ['fullyQualifiedClassParameter', ClassForHinting::class],
            ['arrayParameter', 'array'],
            ['callableParameter', 'callable'],
            ['namespaceClassParameter', ClassForHinting::class],
        ];
    }

    /**
     * @dataProvider typeProvider
     * @parem string $expectedType
     */
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

    /** @return list<array{0: string, 1: bool}> */
    public function allowsNullProvider(): array
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

    /** @dataProvider allowsNullProvider */
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

    /** @return list<array{0: string, 1: bool}> */
    public function isCallableProvider(): array
    {
        return [
            ['noTypeParameter', false],
            ['boolParameter', false],
            ['callableParameter', true],
            ['callableCaseInsensitiveParameter', true],
            ['nullableCallableParameter', true],
            ['unionCallableParameterNullFirst', true],
            ['unionCallableParameterNullLast', true],
            ['unionCallableParameterNullUppercase', true],
            ['unionNotCallableParameter', false],
            ['unionWithCallableNotCallableParameter', false],
            ['unionWithCallableAndObjectNotArrayParameter', false],
            ['intersectionNotCallableParameter', false],
        ];
    }

    /** @dataProvider isCallableProvider */
    public function testIsCallable(string $parameterName, bool $isCallable): void
    {
        $classReflection     = $this->reflector->reflectClass(Methods::class);
        $methodReflection    = $classReflection->getMethod('methodIsCallableParameters');
        $parameterReflection = $methodReflection->getParameter($parameterName);

        self::assertSame($isCallable, $parameterReflection->isCallable());
    }

    /** @return list<array{0: string, 1: bool}> */
    public function isArrayProvider(): array
    {
        return [
            ['noTypeParameter', false],
            ['boolParameter', false],
            ['arrayParameter', true],
            ['arrayCaseInsensitiveParameter', true],
            ['nullableArrayParameter', true],
            ['unionArrayParameterNullFirst', true],
            ['unionArrayParameterNullLast', true],
            ['unionArrayParameterNullUppercase', true],
            ['unionNotArrayParameter', false],
            ['unionWithArrayNotArrayParameter', false],
            ['unionWithArrayAndObjectNotArrayParameter', false],
            ['intersectionNotArrayParameter', false],
        ];
    }

    /** @dataProvider isArrayProvider */
    public function testIsArray(string $parameterName, bool $isArray): void
    {
        $classReflection     = $this->reflector->reflectClass(Methods::class);
        $methodReflection    = $classReflection->getMethod('methodIsArrayParameters');
        $parameterReflection = $methodReflection->getParameter($parameterName);

        self::assertSame($isArray, $parameterReflection->isArray());
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

    public function testGetDeclaringClassForFunctionReturnsNull(): void
    {
        $content = '<?php function myMethod($var = 123) {}';

        $reflector    = new DefaultReflector(new StringSourceLocator($content, $this->astLocator));
        $functionInfo = $reflector->reflectFunction('myMethod');
        $paramInfo    = $functionInfo->getParameter('var');

        self::assertNull($paramInfo->getDeclaringClass());
    }

    /** @return list<array{0: string, 1: string|null}> */
    public function getClassProvider(): array
    {
        return [
            ['untyped', null],
            ['array', null],
            ['object', 'stdClass'],
            ['unionWithClass', 'stdClass'],
            ['unionWithoutClass', null],
            ['intersection', null],
        ];
    }

    /** @dataProvider getClassProvider */
    public function testGetClass(string $parameterName, string|null $className): void
    {
        $reflector = new DefaultReflector(new AggregateSourceLocator([
            new PhpInternalSourceLocator($this->astLocator, $this->sourceStubber),
            new ComposerSourceLocator($GLOBALS['loader'], $this->astLocator),
        ]));

        $classReflection     = $reflector->reflectClass(Methods::class);
        $methodReflection    = $classReflection->getMethod('methodGetClassParameters');
        $parameterReflection = $methodReflection->getParameter($parameterName);

        self::assertSame($className, $parameterReflection->getClass()?->getName());
    }

    public function testCannotClone(): void
    {
        $classInfo  = $this->reflector->reflectClass(Methods::class);
        $methodInfo = $classInfo->getMethod('methodWithParameters');
        $paramInfo  = $methodInfo->getParameter('parameter1');

        $this->expectException(Uncloneable::class);
        clone $paramInfo;
    }

    public function testGetClassFromSelfTypeHintedProperty(): void
    {
        $content = '<?php class Foo { public function myMethod(self $param) {} }';

        $reflector  = new DefaultReflector(new StringSourceLocator($content, $this->astLocator));
        $classInfo  = $reflector->reflectClass('Foo');
        $methodInfo = $classInfo->getMethod('myMethod');

        $hintedClassReflection = $methodInfo->getParameter('param')->getClass();
        self::assertInstanceOf(ReflectionClass::class, $hintedClassReflection);
        self::assertSame('Foo', $hintedClassReflection->getName());
    }

    public function testGetClassFromParentTypeHintedProperty(): void
    {
        $content = '<?php class Foo extends \stdClass { public function myMethod(parent $param) {} }';

        $reflector  = new DefaultReflector(new AggregateSourceLocator([
            new PhpInternalSourceLocator($this->astLocator, $this->sourceStubber),
            new StringSourceLocator($content, $this->astLocator),
        ]));
        $classInfo  = $reflector->reflectClass('Foo');
        $methodInfo = $classInfo->getMethod('myMethod');

        $hintedClassReflection = $methodInfo->getParameter('param')->getClass();
        self::assertInstanceOf(ReflectionClass::class, $hintedClassReflection);
        self::assertSame('stdClass', $hintedClassReflection->getName());
    }

    public function testGetClassFromObjectTypeHintedProperty(): void
    {
        $content = '<?php class Foo { public function myMethod(object $param) {} }';

        $parameter = (new DefaultReflector(new StringSourceLocator($content, $this->astLocator)))
            ->reflectClass(Foo::class)
            ->getMethod('myMethod')
            ->getParameter('param');

        self::assertInstanceOf(ReflectionParameter::class, $parameter);

        self::assertNull($parameter->getClass());

        $type = $parameter->getType();

        self::assertTrue($type->isBuiltin());
        self::assertSame('object', $type->__toString());
    }

    /** @return list<array{0: string, 1: int, 2: int}> */
    public function columnsProvider(): array
    {
        return [
            ["<?php\n\nfunction foo(\n\$test\n) {}", 1, 5],
            ["<?php\n\n    function foo(\n    &\$test) {    \n    }\n", 5, 10],
            ['<?php function foo(...$test) { }', 20, 27],
            ['<?php function foo(array $test = null) { }', 20, 37],
        ];
    }

    /** @dataProvider columnsProvider */
    public function testGetStartColumnAndEndColumn(string $php, int $startColumn, int $endColumn): void
    {
        $reflector = new DefaultReflector(new StringSourceLocator($php, $this->astLocator));
        $function  = $reflector->reflectFunction('foo');
        $parameter = $function->getParameter('test');

        self::assertSame($startColumn, $parameter->getStartColumn());
        self::assertSame($endColumn, $parameter->getEndColumn());
    }

    public function testGetAst(): void
    {
        $php = '<?php function foo($boo) {}';

        $reflector = new DefaultReflector(new StringSourceLocator($php, $this->astLocator));
        $function  = $reflector->reflectFunction('foo');
        $parameter = $function->getParameter('boo');

        $ast = $parameter->getAst();

        self::assertInstanceOf(Param::class, $ast);
        self::assertSame('boo', $ast->var->name);
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
}
