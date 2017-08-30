<?php
declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection;

use Exception;
use Foo;
use InvalidArgumentException;
use LogicException;
use phpDocumentor\Reflection\Types;
use PhpParser\Node\Param;
use PhpParser\PrettyPrinter\Standard as StandardPrettyPrinter;
use PHPUnit\Framework\TestCase;
use Reflector;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\Exception\Uncloneable;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionParameter;
use Roave\BetterReflection\Reflection\ReflectionType;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\Reflector\FunctionReflector;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\ComposerSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\PhpInternalSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;
use Roave\BetterReflectionTest\Fixture\ClassForHinting;
use Roave\BetterReflectionTest\Fixture\ClassWithConstantsAsDefaultValues;
use Roave\BetterReflectionTest\Fixture\Methods;
use Roave\BetterReflectionTest\Fixture\Php71NullableParameterTypeDeclarations;
use Roave\BetterReflectionTest\Fixture\Php7ParameterTypeDeclarations;
use Roave\BetterReflectionTest\FixtureOther\OtherClass;
use SplDoublyLinkedList;
use stdClass;

/**
 * @covers \Roave\BetterReflection\Reflection\ReflectionParameter
 */
class ReflectionParameterTest extends TestCase
{
    /**
     * @var ClassReflector
     */
    private $reflector;

    /**
     * @var Locator
     */
    private $astLocator;

    public function setUp() : void
    {
        global $loader;

        $this->astLocator = (new BetterReflection())->astLocator();
        $this->reflector  = new ClassReflector(new ComposerSourceLocator($loader, $this->astLocator));
    }

    public function testCreateFromClassNameAndMethod() : void
    {
        $parameterInfo = ReflectionParameter::createFromClassNameAndMethod(SplDoublyLinkedList::class, 'add', 'index');

        self::assertInstanceOf(ReflectionParameter::class, $parameterInfo);
        self::assertSame('index', $parameterInfo->getName());
    }

    public function testCreateFromClassInstanceAndMethod() : void
    {
        $parameterInfo = ReflectionParameter::createFromClassInstanceAndMethod(new SplDoublyLinkedList(), 'add', 'index');

        self::assertInstanceOf(ReflectionParameter::class, $parameterInfo);
        self::assertSame('index', $parameterInfo->getName());
    }

    public function testCreateFromCallable() : void
    {
        $parameterInfo = ReflectionParameter::createFromClosure(function ($a) : void {
        }, 'a');

        self::assertInstanceOf(ReflectionParameter::class, $parameterInfo);
        self::assertSame('a', $parameterInfo->getName());
    }

    public function testCreateFromSpecWithArray() : void
    {
        $parameterInfo = ReflectionParameter::createFromSpec([SplDoublyLinkedList::class, 'add'], 'index');

        self::assertInstanceOf(ReflectionParameter::class, $parameterInfo);
        self::assertSame('index', $parameterInfo->getName());
    }

    public function testCreateFromSpecWithArrayWithInstance() : void
    {
        $splDoublyLinkedList = new SplDoublyLinkedList();
        $parameterInfo       = ReflectionParameter::createFromSpec([$splDoublyLinkedList, 'add'], 'index');

        self::assertInstanceOf(ReflectionParameter::class, $parameterInfo);
        self::assertSame('index', $parameterInfo->getName());
    }

    public function testCreateFromSpecWithFunctionName() : void
    {
        require_once __DIR__ . '/../Fixture/ClassForHinting.php';
        $parameterInfo = ReflectionParameter::createFromSpec('Roave\BetterReflectionTest\Fixture\testFunction', 'param1');

        self::assertInstanceOf(ReflectionParameter::class, $parameterInfo);
        self::assertSame('param1', $parameterInfo->getName());
    }

    public function testCreateFromSpecWithClosure() : void
    {
        $parameterInfo = ReflectionParameter::createFromSpec(function ($a) : void {
        }, 'a');

        self::assertInstanceOf(ReflectionParameter::class, $parameterInfo);
        self::assertSame('a', $parameterInfo->getName());
    }

    public function testCreateFromSpecWithInvalidArgumentThrowsException() : void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Could not create reflection from the spec given');
        ReflectionParameter::createFromSpec(123, 'a');
    }

    public function testImplementsReflector() : void
    {
        $classInfo  = $this->reflector->reflect(Methods::class);
        $methodInfo = $classInfo->getMethod('methodWithParameters');
        $paramInfo  = $methodInfo->getParameter('parameter1');

        self::assertInstanceOf(Reflector::class, $paramInfo);
    }

    public function testExportThrowsException() : void
    {
        $this->expectException(Exception::class);
        ReflectionParameter::export();
    }

    /**
     * @return array
     */
    public function defaultParameterProvider() : array
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
     * @param string $defaultExpression
     * @param mixed $expectedValue
     * @dataProvider defaultParameterProvider
     */
    public function testDefaultParametersTypes(string $defaultExpression, $expectedValue) : void
    {
        $content = "<?php class Foo { public function myMethod(\$var = $defaultExpression) {} }";

        $reflector   = new ClassReflector(new StringSourceLocator($content, $this->astLocator));
        $classInfo   = $reflector->reflect('Foo');
        $methodInfo  = $classInfo->getMethod('myMethod');
        $paramInfo   = $methodInfo->getParameter('var');
        $actualValue = $paramInfo->getDefaultValue();

        self::assertSame($expectedValue, $actualValue);
    }

    public function testGetDefaultValueWhenDefaultValueNotAvailableThrowsException() : void
    {
        $content = '<?php class Foo { public function myMethod($var) {} }';

        $reflector  = new ClassReflector(new StringSourceLocator($content, $this->astLocator));
        $classInfo  = $reflector->reflect('Foo');
        $methodInfo = $classInfo->getMethod('myMethod');
        $paramInfo  = $methodInfo->getParameter('var');

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('This parameter does not have a default value available');
        $paramInfo->getDefaultValue();
    }

    public function testGetDocBlockTypeStrings() : void
    {
        $classInfo = $this->reflector->reflect(Methods::class);

        $method = $classInfo->getMethod('methodWithParameters');

        $param1 = $method->getParameter('parameter1');
        self::assertSame(['string'], $param1->getDocBlockTypeStrings());

        $param2 = $method->getParameter('parameter2');
        self::assertSame(['int', 'float'], $param2->getDocBlockTypeStrings());
    }

    public function testGetDocBlockTypes() : void
    {
        $classInfo = $this->reflector->reflect(Methods::class);

        $method = $classInfo->getMethod('methodWithParameters');

        $param1      = $method->getParameter('parameter1');
        $param1Types = $param1->getDocBlockTypes();
        self::assertCount(1, $param1Types);
        self::assertInstanceOf(Types\String_::class, $param1Types[0]);

        $param2      = $method->getParameter('parameter2');
        $param2Types = $param2->getDocBlockTypes();
        self::assertCount(2, $param2Types);
        self::assertInstanceOf(Types\Integer::class, $param2Types[0]);
        self::assertInstanceOf(Types\Float_::class, $param2Types[1]);
    }

    public function testStringCast() : void
    {
        $classInfo = $this->reflector->reflect(Methods::class);
        $method    = $classInfo->getMethod('methodWithOptionalParameters');

        $requiredParam = $method->getParameter('parameter');
        self::assertSame('Parameter #0 [ <required> $parameter ]', (string) $requiredParam);

        $optionalParam = $method->getParameter('optionalParameter');
        self::assertSame('Parameter #1 [ <optional> $optionalParameter = NULL ]', (string) $optionalParam);
    }

    public function testGetPosition() : void
    {
        $classInfo = $this->reflector->reflect(Methods::class);

        $method = $classInfo->getMethod('methodWithParameters');

        $param1 = $method->getParameter('parameter1');
        self::assertSame(0, $param1->getPosition());

        $param2 = $method->getParameter('parameter2');
        self::assertSame(1, $param2->getPosition());
    }

    /**
     * @return array
     */
    public function typeProvider() : array
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
     * @param string $parameterToTest
     * @parem string $expectedType
     */
    public function testGetType(
        string $parameterToTest,
        string $expectedType
    ) : void {
        $classInfo = $this->reflector->reflect(Methods::class);

        $method = $classInfo->getMethod('methodWithExplicitTypedParameters');

        $type = $method->getParameter($parameterToTest)->getType();

        self::assertSame($expectedType, (string) $type);
    }

    public function testPhp7TypeDeclarationWithIntBuiltinType() : void
    {
        $classInfo = $this->reflector->reflect(Php7ParameterTypeDeclarations::class);
        $method    = $classInfo->getMethod('foo');

        $intParamType = $method->getParameter('intParam')->getType();
        self::assertSame('int', (string) $intParamType);
        self::assertTrue($intParamType->isBuiltin());
        self::assertFalse($intParamType->allowsNull());
    }

    public function testPhp7TypeDeclarationWithClassTypeIsNotBuiltin() : void
    {
        $classInfo = $this->reflector->reflect(Php7ParameterTypeDeclarations::class);
        $method    = $classInfo->getMethod('foo');

        $classParamType = $method->getParameter('classParam')->getType();
        self::assertSame(stdClass::class, (string) $classParamType);
        self::assertFalse($classParamType->isBuiltin());
        self::assertFalse($classParamType->allowsNull());
    }

    public function testPhp7TypeDeclarationWithoutType() : void
    {
        $classInfo = $this->reflector->reflect(Php7ParameterTypeDeclarations::class);
        $method    = $classInfo->getMethod('foo');

        self::assertNull($method->getParameter('noTypeParam')->getType());
    }

    public function testPhp7TypeDeclarationWithStringTypeThatAllowsNull() : void
    {
        $classInfo = $this->reflector->reflect(Php7ParameterTypeDeclarations::class);
        $method    = $classInfo->getMethod('foo');

        $stringParamType = $method->getParameter('stringParamAllowsNull')->getType();
        self::assertSame('string', (string) $stringParamType);
        self::assertTrue($stringParamType->isBuiltin());
        self::assertTrue($stringParamType->allowsNull());
    }

    public function nullableParameterTypeFunctionProvider() : array
    {
        return [
            ['nullableIntParam', 'int'],
            ['nullableClassParam', stdClass::class],
            ['nullableStringParamWithDefaultValue', 'string'],
        ];
    }

    /**
     * @param string $parameterToReflect
     * @param string $expectedType
     * @dataProvider nullableParameterTypeFunctionProvider
     */
    public function testGetNullableReturnTypeWithDeclaredType(string $parameterToReflect, string $expectedType) : void
    {
        $classInfo = $this->reflector->reflect(Php71NullableParameterTypeDeclarations::class);
        $parameter = $classInfo->getMethod('foo')->getParameter($parameterToReflect);

        $reflectionType = $parameter->getType();
        self::assertInstanceOf(ReflectionType::class, $reflectionType);
        self::assertSame($expectedType, (string) $reflectionType);
        self::assertTrue($reflectionType->allowsNull());
    }

    public function testHasTypeReturnsTrueWithType() : void
    {
        $classInfo = $this->reflector->reflect(Php7ParameterTypeDeclarations::class);
        $method    = $classInfo->getMethod('foo');

        self::assertTrue($method->getParameter('intParam')->hasType());
    }

    public function testHasTypeReturnsFalseWithoutType() : void
    {
        $classInfo = $this->reflector->reflect(Php7ParameterTypeDeclarations::class);
        $method    = $classInfo->getMethod('foo');

        self::assertFalse($method->getParameter('noTypeParam')->hasType());
    }

    public function testSetType() : void
    {
        $classInfo     = $this->reflector->reflect(Php7ParameterTypeDeclarations::class);
        $methodInfo    = $classInfo->getMethod('foo');
        $parameterInfo = $methodInfo->getParameter('intParam');

        $parameterInfo->setType('string');

        self::assertSame('string', (string) $parameterInfo->getType());
        self::assertStringStartsWith(
            'public function foo(string $intParam',
            (new StandardPrettyPrinter())->prettyPrint([$methodInfo->getAst()])
        );
    }

    public function testIsCallable() : void
    {
        $classInfo = $this->reflector->reflect(Methods::class);

        $method = $classInfo->getMethod('methodWithExplicitTypedParameters');

        $nonCallableParam = $method->getParameter('stdClassParameter');
        self::assertFalse($nonCallableParam->isCallable());

        $callableParam = $method->getParameter('callableParameter');
        self::assertTrue($callableParam->isCallable());
    }

    public function testIsArray() : void
    {
        $classInfo = $this->reflector->reflect(Methods::class);

        $method = $classInfo->getMethod('methodWithExplicitTypedParameters');

        $nonArrayParam = $method->getParameter('stdClassParameter');
        self::assertFalse($nonArrayParam->isArray());

        $arrayParam = $method->getParameter('arrayParameter');
        self::assertTrue($arrayParam->isArray());
    }

    public function testIsVariadic() : void
    {
        $classInfo = $this->reflector->reflect(Methods::class);

        $method = $classInfo->getMethod('methodWithVariadic');

        $nonVariadicParam = $method->getParameter('nonVariadicParameter');
        self::assertFalse($nonVariadicParam->isVariadic());

        $variadicParam = $method->getParameter('variadicParameter');
        self::assertTrue($variadicParam->isVariadic());
    }

    public function testIsPassedByReference() : void
    {
        $classInfo = $this->reflector->reflect(Methods::class);

        $method = $classInfo->getMethod('methodWithReference');

        $nonRefParam = $method->getParameter('nonRefParameter');
        self::assertFalse($nonRefParam->isPassedByReference());
        self::assertTrue($nonRefParam->canBePassedByValue());

        $refParam = $method->getParameter('refParameter');
        self::assertTrue($refParam->isPassedByReference());
        self::assertFalse($refParam->canBePassedByValue());
    }

    public function testGetDefaultValueAndIsOptional() : void
    {
        $classInfo = $this->reflector->reflect(Methods::class);
        $method    = $classInfo->getMethod('methodWithNonOptionalDefaultValue');

        $firstParam = $method->getParameter('firstParameter');
        self::assertFalse($firstParam->isOptional());
        self::assertTrue($firstParam->isDefaultValueAvailable());

        $secondParam = $method->getParameter('secondParameter');
        self::assertFalse($secondParam->isOptional());
        self::assertFalse($secondParam->isDefaultValueAvailable());
    }

    /**
     * @group 109
     */
    public function testVariadicParametersAreAlsoImplicitlyOptional() : void
    {
        $classInfo = $this->reflector->reflect(Methods::class);

        $method = $classInfo->getMethod('methodWithVariadic');

        $nonVariadicParam = $method->getParameter('nonVariadicParameter');
        self::assertFalse($nonVariadicParam->isVariadic());
        self::assertFalse($nonVariadicParam->isOptional());

        $variadicParam = $method->getParameter('variadicParameter');
        self::assertTrue($variadicParam->isVariadic());
        self::assertTrue($variadicParam->isOptional());
    }

    public function testAllowsNull() : void
    {
        $classInfo = $this->reflector->reflect(Methods::class);
        $method    = $classInfo->getMethod('methodToCheckAllowsNull');

        $firstParam = $method->getParameter('allowsNull');
        self::assertTrue($firstParam->allowsNull());

        $secondParam = $method->getParameter('hintDisallowNull');
        self::assertFalse($secondParam->allowsNull());

        $thirdParam = $method->getParameter('hintAllowNull');
        self::assertTrue($thirdParam->allowsNull());
    }

    public function testIsDefaultValueConstantAndGetDefaultValueConstantName() : void
    {
        $classInfo = $this->reflector->reflect(Methods::class);
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

    public function testGetDefaultValueConstantNameClassConstants() : void
    {
        $reflector = new ClassReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/ClassWithConstantsAsDefaultValues.php',
            $this->astLocator
        ));
        $classInfo = $reflector->reflect(ClassWithConstantsAsDefaultValues::class);
        $method    = $classInfo->getMethod('method');

        $param1 = $method->getParameter('param1');
        self::assertSame(ClassWithConstantsAsDefaultValues::class . '::MY_CONST', $param1->getDefaultValueConstantName());

        $param2 = $method->getParameter('param2');
        self::assertSame(ClassWithConstantsAsDefaultValues::class . '::PARENT_CONST', $param2->getDefaultValueConstantName());

        $param3 = $method->getParameter('param3');
        self::assertSame(OtherClass::class . '::MY_CONST', $param3->getDefaultValueConstantName());
    }

    public function testGetDefaultValueConstantNameNamespacedConstants() : void
    {
        $this->markTestSkipped('@todo - implement reflection of constants outside a class');

        $reflector = new ClassReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/ClassWithConstantsAsDefaultValues.php',
            $this->astLocator
        ));
        $classInfo = $reflector->reflect(ClassWithConstantsAsDefaultValues::class);
        $method    = $classInfo->getMethod('method');

        $param4 = $method->getParameter('param4');
        self::assertSame('Roave\BetterReflectionTest\Fixture\THIS_NAMESPACE_CONST', $param4->getDefaultValueConstantName());

        $param5 = $method->getParameter('param5');
        self::assertSame('Roave\BetterReflectionTest\FixtureOther\OTHER_NAMESPACE_CONST', $param5->getDefaultValueConstantName());
    }

    public function testGetDeclaringFunction() : void
    {
        $content = '<?php class Foo { public function myMethod($var = 123) {} }';

        $reflector  = new ClassReflector(new StringSourceLocator($content, $this->astLocator));
        $classInfo  = $reflector->reflect('Foo');
        $methodInfo = $classInfo->getMethod('myMethod');
        $paramInfo  = $methodInfo->getParameter('var');

        self::assertSame($methodInfo, $paramInfo->getDeclaringFunction());
    }

    public function testGetDeclaringClassForMethod() : void
    {
        $content = '<?php class Foo { public function myMethod($var = 123) {} }';

        $reflector  = new ClassReflector(new StringSourceLocator($content, $this->astLocator));
        $classInfo  = $reflector->reflect('Foo');
        $methodInfo = $classInfo->getMethod('myMethod');
        $paramInfo  = $methodInfo->getParameter('var');

        self::assertSame($classInfo, $paramInfo->getDeclaringClass());
    }

    public function testGetDeclaringClassForFunctionReturnsNull() : void
    {
        $content = '<?php function myMethod($var = 123) {}';

        $reflector    = new FunctionReflector(new StringSourceLocator($content, $this->astLocator), $this->reflector);
        $functionInfo = $reflector->reflect('myMethod');
        $paramInfo    = $functionInfo->getParameter('var');

        self::assertNull($paramInfo->getDeclaringClass());
    }

    public function defaultValueStringProvider() : array
    {
        return [
            ['123', '123'],
            ['12.3', '12.3'],
            ['true', 'true'],
            ['false', 'false'],
            ['null', 'NULL'],
            ['[]', "array (\n)"],
            ['[1, 2, 3]', "array (\n  0 => 1,\n  1 => 2,\n  2 => 3,\n)"],
            ['"foo"', "'foo'"],
        ];
    }

    /**
     * @param string $defaultValue
     * @param string $expectedValue
     * @dataProvider defaultValueStringProvider
     * @deprecated
     */
    public function testGetDefaultValueAsString(string $defaultValue, string $expectedValue) : void
    {
        $content = "<?php function myMethod(\$var = $defaultValue) {}";

        $reflector    = new FunctionReflector(new StringSourceLocator($content, $this->astLocator), $this->reflector);
        $functionInfo = $reflector->reflect('myMethod');
        $paramInfo    = $functionInfo->getParameter('var');

        // Must be starts with because PHP (sometimes value is 12.300000000000001)
        self::assertStringStartsWith($expectedValue, $paramInfo->getDefaultValueAsString());
    }

    public function testGetClassForTypeHintedMethodParameters() : void
    {
        $content = '<?php class Foo { public function myMethod($untyped, array $array, \stdClass $object) {} }';

        $reflector  = new ClassReflector(new AggregateSourceLocator([
            new PhpInternalSourceLocator($this->astLocator),
            new StringSourceLocator($content, $this->astLocator),
        ]));
        $classInfo  = $reflector->reflect('Foo');
        $methodInfo = $classInfo->getMethod('myMethod');

        self::assertNull($methodInfo->getParameter('untyped')->getClass());
        self::assertNull($methodInfo->getParameter('array')->getClass());

        $hintedClassReflection = $methodInfo->getParameter('object')->getClass();
        self::assertInstanceOf(ReflectionClass::class, $hintedClassReflection);
        self::assertSame('stdClass', $hintedClassReflection->getName());
    }

    public function testCannotClone() : void
    {
        $classInfo  = $this->reflector->reflect(Methods::class);
        $methodInfo = $classInfo->getMethod('methodWithParameters');
        $paramInfo  = $methodInfo->getParameter('parameter1');

        $this->expectException(Uncloneable::class);
        $unused = clone $paramInfo;
    }

    public function testGetClassFromSelfTypeHintedProperty() : void
    {
        $content = '<?php class Foo { public function myMethod(self $param) {} }';

        $reflector  = new ClassReflector(new AggregateSourceLocator([
            new StringSourceLocator($content, $this->astLocator),
        ]));
        $classInfo  = $reflector->reflect('Foo');
        $methodInfo = $classInfo->getMethod('myMethod');

        $hintedClassReflection = $methodInfo->getParameter('param')->getClass();
        self::assertInstanceOf(ReflectionClass::class, $hintedClassReflection);
        self::assertSame('Foo', $hintedClassReflection->getName());
    }

    public function testGetClassFromParentTypeHintedProperty() : void
    {
        $content = '<?php class Foo extends \stdClass { public function myMethod(parent $param) {} }';

        $reflector  = new ClassReflector(new AggregateSourceLocator([
            new PhpInternalSourceLocator($this->astLocator),
            new StringSourceLocator($content, $this->astLocator),
        ]));
        $classInfo  = $reflector->reflect('Foo');
        $methodInfo = $classInfo->getMethod('myMethod');

        $hintedClassReflection = $methodInfo->getParameter('param')->getClass();
        self::assertInstanceOf(ReflectionClass::class, $hintedClassReflection);
        self::assertSame('stdClass', $hintedClassReflection->getName());
    }

    public function testGetClassFromObjectTypeHintedProperty() : void
    {
        $content = '<?php class Foo { public function myMethod(object $param) {} }';

        $parameter = (new ClassReflector(new StringSourceLocator($content, $this->astLocator)))
            ->reflect(Foo::class)
            ->getMethod('myMethod')
            ->getParameter('param');

        self::assertInstanceOf(ReflectionParameter::class, $parameter);

        self::assertNull($parameter->getClass());

        $type = $parameter->getType();

        self::assertTrue($type->isBuiltin());
        self::assertSame('object', $type->__toString());
    }

    public function columnsProvider() : array
    {
        return [
            ["<?php\n\nfunction foo(\n\$test\n) {}", 1, 5],
            ["<?php\n\n    function foo(\n    &\$test) {    \n    }\n", 5, 10],
            ['<?php function foo(...$test) { }', 20, 27],
            ['<?php function foo(array $test = null) { }', 20, 37],
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
        $reflector = new FunctionReflector(new StringSourceLocator($php, $this->astLocator), $this->reflector);
        $function  = $reflector->reflect('foo');
        $parameter = $function->getParameter('test');

        self::assertSame($startColumn, $parameter->getStartColumn());
        self::assertSame($endColumn, $parameter->getEndColumn());
    }

    public function testGetAst() : void
    {
        $php = '<?php function foo($a = 123) {}';

        $functionReflection  = (new FunctionReflector(new StringSourceLocator($php, $this->astLocator), $this->reflector))->reflect('foo');
        $parameterReflection = $functionReflection->getParameter('a');

        $ast = $parameterReflection->getAst();

        self::assertInstanceOf(Param::class, $ast);
        self::assertSame('a', $ast->name);
    }
}
