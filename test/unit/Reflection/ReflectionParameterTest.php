<?php

namespace Roave\BetterReflectionTest\Reflection;

use Roave\BetterReflection\Reflection\Exception\Uncloneable;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionParameter;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\Reflector\FunctionReflector;
use Roave\BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\PhpInternalSourceLocator;
use Roave\BetterReflectionTest\Fixture\ClassForHinting;
use Roave\BetterReflectionTest\Fixture\Methods;
use Roave\BetterReflectionTest\Fixture\Php7ParameterTypeDeclarations;
use phpDocumentor\Reflection\Types;
use Roave\BetterReflection\SourceLocator\Type\ComposerSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;
use PhpParser\PrettyPrinter\Standard as StandardPrettyPrinter;

/**
 * @covers \Roave\BetterReflection\Reflection\ReflectionParameter
 */
class ReflectionParameterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ClassReflector
     */
    private $reflector;

    public function setUp()
    {
        global $loader;
        $this->reflector = new ClassReflector(new ComposerSourceLocator($loader));
    }

    public function testCreateFromClassNameAndMethod()
    {
        $parameterInfo = ReflectionParameter::createFromClassNameAndMethod(\SplDoublyLinkedList::class, 'add', 'index');

        self::assertInstanceOf(ReflectionParameter::class, $parameterInfo);
        self::assertSame('index', $parameterInfo->getName());
    }

    public function testCreateFromClassInstanceAndMethod()
    {
        $parameterInfo = ReflectionParameter::createFromClassInstanceAndMethod(new \SplDoublyLinkedList(), 'add', 'index');

        self::assertInstanceOf(ReflectionParameter::class, $parameterInfo);
        self::assertSame('index', $parameterInfo->getName());
    }

    public function testCreateFromCallable()
    {
        $parameterInfo = ReflectionParameter::createFromClosure(function ($a) {}, 'a');

        self::assertInstanceOf(ReflectionParameter::class, $parameterInfo);
        self::assertSame('a', $parameterInfo->getName());
    }

    public function testCreateFromSpecWithArray()
    {
        $parameterInfo = ReflectionParameter::createFromSpec([\SplDoublyLinkedList::class, 'add'], 'index');

        self::assertInstanceOf(ReflectionParameter::class, $parameterInfo);
        self::assertSame('index', $parameterInfo->getName());
    }

    public function testCreateFromSpecWithArrayWithInstance()
    {
        $splDoublyLinkedList = new \SplDoublyLinkedList();
        $parameterInfo = ReflectionParameter::createFromSpec([$splDoublyLinkedList, 'add'], 'index');

        self::assertInstanceOf(ReflectionParameter::class, $parameterInfo);
        self::assertSame('index', $parameterInfo->getName());
    }

    public function testCreateFromSpecWithFunctionName()
    {
        require_once __DIR__ . '/../Fixture/ClassForHinting.php';
        $parameterInfo = ReflectionParameter::createFromSpec('Roave\BetterReflectionTest\Fixture\testFunction', 'param1');

        self::assertInstanceOf(ReflectionParameter::class, $parameterInfo);
        self::assertSame('param1', $parameterInfo->getName());
    }

    public function testCreateFromSpecWithClosure()
    {
        $parameterInfo = ReflectionParameter::createFromSpec(function ($a) {}, 'a');

        self::assertInstanceOf(ReflectionParameter::class, $parameterInfo);
        self::assertSame('a', $parameterInfo->getName());
    }

    public function testCreateFromSpecWithInvalidArgumentThrowsException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Could not create reflection from the spec given');
        ReflectionParameter::createFromSpec(123, 'a');
    }

    public function testImplementsReflector()
    {
        $classInfo = $this->reflector->reflect(Methods::class);
        $methodInfo = $classInfo->getMethod('methodWithParameters');
        $paramInfo = $methodInfo->getParameter('parameter1');

        self::assertInstanceOf(\Reflector::class, $paramInfo);
    }

    public function testExportThrowsException()
    {
        $this->expectException(\Exception::class);
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
    public function testDefaultParametersTypes(string $defaultExpression, $expectedValue)
    {
        $content = "<?php class Foo { public function myMethod(\$var = $defaultExpression) {} }";

        $reflector = new ClassReflector(new StringSourceLocator($content));
        $classInfo = $reflector->reflect('Foo');
        $methodInfo = $classInfo->getMethod('myMethod');
        $paramInfo = $methodInfo->getParameter('var');
        $actualValue = $paramInfo->getDefaultValue();

        self::assertSame($expectedValue, $actualValue);
    }

    public function testGetDefaultValueWhenDefaultValueNotAvailableThrowsException()
    {
        $content = '<?php class Foo { public function myMethod($var) {} }';

        $reflector = new ClassReflector(new StringSourceLocator($content));
        $classInfo = $reflector->reflect('Foo');
        $methodInfo = $classInfo->getMethod('myMethod');
        $paramInfo = $methodInfo->getParameter('var');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('This parameter does not have a default value available');
        $paramInfo->getDefaultValue();
    }

    public function testGetDocBlockTypeStrings()
    {
        $classInfo = $this->reflector->reflect(Methods::class);

        $method = $classInfo->getMethod('methodWithParameters');

        $param1 = $method->getParameter('parameter1');
        self::assertSame(['string'], $param1->getDocBlockTypeStrings());

        $param2 = $method->getParameter('parameter2');
        self::assertSame(['int', 'float'], $param2->getDocBlockTypeStrings());
    }

    public function testGetDocBlockTypes()
    {
        $classInfo = $this->reflector->reflect(Methods::class);

        $method = $classInfo->getMethod('methodWithParameters');

        $param1 = $method->getParameter('parameter1');
        $param1Types = $param1->getDocBlockTypes();
        self::assertCount(1, $param1Types);
        self::assertInstanceOf(Types\String_::class, $param1Types[0]);

        $param2 = $method->getParameter('parameter2');
        $param2Types = $param2->getDocBlockTypes();
        self::assertCount(2, $param2Types);
        self::assertInstanceOf(Types\Integer::class, $param2Types[0]);
        self::assertInstanceOf(Types\Float_::class, $param2Types[1]);
    }

    public function testStringCast()
    {
        $classInfo = $this->reflector->reflect(Methods::class);
        $method = $classInfo->getMethod('methodWithOptionalParameters');

        $requiredParam = $method->getParameter('parameter');
        self::assertSame('Parameter #0 [ <required> $parameter ]', (string)$requiredParam);

        $optionalParam = $method->getParameter('optionalParameter');
        self::assertSame('Parameter #1 [ <optional> $optionalParameter = NULL ]', (string)$optionalParam);
    }

    public function testGetPosition()
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
    public function typeHintProvider() : array
    {
        return [
            ['stdClassParameter', Types\Object_::class, '\stdClass', 'stdClass'],
            ['fullyQualifiedClassParameter', Types\Object_::class, '\\' . ClassForHinting::class, 'ClassForHinting'],
            ['arrayParameter', Types\Array_::class],
            ['callableParameter', Types\Callable_::class],
            ['namespaceClassParameter', Types\Object_::class, '\\' . ClassForHinting::class, 'ClassForHinting'],
        ];
    }

    /**
     * @dataProvider typeHintProvider
     * @param string $parameterToTest
     * @param string $expectedType
     * @param string|null $expectedFqsen
     * @param string|null $expectedFqsenName
     */
    public function testGetTypeHint(
        string $parameterToTest,
        string $expectedType,
        string $expectedFqsen = null,
        string $expectedFqsenName = null
    ) {
        $classInfo = $this->reflector->reflect(Methods::class);

        $method = $classInfo->getMethod('methodWithExplicitTypedParameters');

        $type = $method->getParameter($parameterToTest)->getTypeHint();
        self::assertInstanceOf($expectedType, $type);

        if (null !== $expectedFqsen) {
            self::assertSame($expectedFqsen, (string)$type->getFqsen());
        }

        if (null !== $expectedFqsenName) {
            self::assertSame($expectedFqsenName, $type->getFqsen()->getName());
        }
    }

    public function testPhp7TypeDeclarationWithIntBuiltinType()
    {
        $classInfo = $this->reflector->reflect(Php7ParameterTypeDeclarations::class);
        $method = $classInfo->getMethod('foo');

        $intParamType = $method->getParameter('intParam')->getType();
        self::assertSame('int', (string)$intParamType);
        self::assertTrue($intParamType->isBuiltin());
        self::assertFalse($intParamType->allowsNull());
    }

    public function testPhp7TypeDeclarationWithClassTypeIsNotBuiltin()
    {
        $classInfo = $this->reflector->reflect(Php7ParameterTypeDeclarations::class);
        $method = $classInfo->getMethod('foo');

        $classParamType = $method->getParameter('classParam')->getType();
        self::assertSame(\stdClass::class, (string)$classParamType);
        self::assertFalse($classParamType->isBuiltin());
        self::assertFalse($classParamType->allowsNull());
    }

    public function testPhp7TypeDeclarationWithoutType()
    {
        $classInfo = $this->reflector->reflect(Php7ParameterTypeDeclarations::class);
        $method = $classInfo->getMethod('foo');

        self::assertNull($method->getParameter('noTypeParam')->getType());
    }

    public function testPhp7TypeDeclarationWithStringTypeThatAllowsNull()
    {
        $classInfo = $this->reflector->reflect(Php7ParameterTypeDeclarations::class);
        $method = $classInfo->getMethod('foo');

        $stringParamType = $method->getParameter('stringParamAllowsNull')->getType();
        self::assertSame('string', (string)$stringParamType);
        self::assertTrue($stringParamType->isBuiltin());
        self::assertTrue($stringParamType->allowsNull());
    }

    public function testHasTypeReturnsTrueWithType()
    {
        $classInfo = $this->reflector->reflect(Php7ParameterTypeDeclarations::class);
        $method = $classInfo->getMethod('foo');

        self::assertTrue($method->getParameter('intParam')->hasType());
    }

    public function testHasTypeReturnsFalseWithoutType()
    {
        $classInfo = $this->reflector->reflect(Php7ParameterTypeDeclarations::class);
        $method = $classInfo->getMethod('foo');

        self::assertFalse($method->getParameter('noTypeParam')->hasType());
    }

    public function testSetType()
    {
        $classInfo = $this->reflector->reflect(Php7ParameterTypeDeclarations::class);
        $methodInfo = $classInfo->getMethod('foo');
        $parameterInfo = $methodInfo->getParameter('intParam');

        $parameterInfo->setType(new Types\String_());

        self::assertSame('string', (string)$parameterInfo->getType());
        self::assertStringStartsWith(
            'public function foo(string $intParam',
            (new StandardPrettyPrinter())->prettyPrint([$methodInfo->getAst()])
        );
    }

    public function testRemoveType()
    {
        $classInfo = $this->reflector->reflect(Php7ParameterTypeDeclarations::class);
        $methodInfo = $classInfo->getMethod('foo');
        $parameterInfo = $methodInfo->getParameter('intParam');

        $parameterInfo->removeType();

        self::assertNull($parameterInfo->getType());
        self::assertStringStartsWith(
            'public function foo($intParam',
            (new StandardPrettyPrinter())->prettyPrint([$methodInfo->getAst()])
        );
    }

    public function testIsCallable()
    {
        $classInfo = $this->reflector->reflect(Methods::class);

        $method = $classInfo->getMethod('methodWithExplicitTypedParameters');

        $nonCallableParam = $method->getParameter('stdClassParameter');
        self::assertFalse($nonCallableParam->isCallable());

        $callableParam = $method->getParameter('callableParameter');
        self::assertTrue($callableParam->isCallable());
    }

    public function testIsArray()
    {
        $classInfo = $this->reflector->reflect(Methods::class);

        $method = $classInfo->getMethod('methodWithExplicitTypedParameters');

        $nonArrayParam = $method->getParameter('stdClassParameter');
        self::assertFalse($nonArrayParam->isArray());

        $arrayParam = $method->getParameter('arrayParameter');
        self::assertTrue($arrayParam->isArray());
    }

    public function testIsVariadic()
    {
        $classInfo = $this->reflector->reflect(Methods::class);

        $method = $classInfo->getMethod('methodWithVariadic');

        $nonVariadicParam = $method->getParameter('nonVariadicParameter');
        self::assertFalse($nonVariadicParam->isVariadic());

        $variadicParam = $method->getParameter('variadicParameter');
        self::assertTrue($variadicParam->isVariadic());
    }

    public function testIsPassedByReference()
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

    public function testGetDefaultValueAndIsOptional()
    {
        $classInfo = $this->reflector->reflect(Methods::class);
        $method = $classInfo->getMethod('methodWithNonOptionalDefaultValue');

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
    public function testVariadicParametersAreAlsoImplicitlyOptional()
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

    public function testAllowsNull()
    {
        $classInfo = $this->reflector->reflect(Methods::class);
        $method = $classInfo->getMethod('methodToCheckAllowsNull');

        $firstParam = $method->getParameter('allowsNull');
        self::assertTrue($firstParam->allowsNull());

        $secondParam = $method->getParameter('hintDisallowNull');
        self::assertFalse($secondParam->allowsNull());

        $thirdParam = $method->getParameter('hintAllowNull');
        self::assertTrue($thirdParam->allowsNull());
    }

    public function testIsDefaultValueConstantAndGetDefaultValueConstantName()
    {
        $classInfo = $this->reflector->reflect(Methods::class);
        $method = $classInfo->getMethod('methodWithConstAsDefault');

        $constDefault = $method->getParameter('constDefault');
        self::assertTrue($constDefault->isDefaultValueConstant());
        self::assertSame('SOME_CONST', $constDefault->getDefaultValueConstantName());

        $definedDefault = $method->getParameter('definedDefault');
        self::assertTrue($definedDefault->isDefaultValueConstant());
        self::assertSame('SOME_DEFINED_VALUE', $definedDefault->getDefaultValueConstantName());

        $intDefault = $method->getParameter('intDefault');
        self::assertFalse($intDefault->isDefaultValueConstant());

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('This parameter is not a constant default value, so cannot have a constant name');
        $intDefault->getDefaultValueConstantName();
    }

    public function testGetDeclaringFunction()
    {
        $content = '<?php class Foo { public function myMethod($var = 123) {} }';

        $reflector = new ClassReflector(new StringSourceLocator($content));
        $classInfo = $reflector->reflect('Foo');
        $methodInfo = $classInfo->getMethod('myMethod');
        $paramInfo = $methodInfo->getParameter('var');

        self::assertSame($methodInfo, $paramInfo->getDeclaringFunction());
    }

    public function testGetDeclaringClassForMethod()
    {
        $content = '<?php class Foo { public function myMethod($var = 123) {} }';

        $reflector = new ClassReflector(new StringSourceLocator($content));
        $classInfo = $reflector->reflect('Foo');
        $methodInfo = $classInfo->getMethod('myMethod');
        $paramInfo = $methodInfo->getParameter('var');

        self::assertSame($classInfo, $paramInfo->getDeclaringClass());
    }

    public function testGetDeclaringClassForFunctionReturnsNull()
    {
        $content = '<?php function myMethod($var = 123) {}';

        $reflector = new FunctionReflector(new StringSourceLocator($content));
        $functionInfo = $reflector->reflect('myMethod');
        $paramInfo = $functionInfo->getParameter('var');

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
     */
    public function testGetDefaultValueAsString(string $defaultValue, string $expectedValue)
    {
        $content = "<?php function myMethod(\$var = $defaultValue) {}";

        $reflector = new FunctionReflector(new StringSourceLocator($content));
        $functionInfo = $reflector->reflect('myMethod');
        $paramInfo = $functionInfo->getParameter('var');

        // Must be starts with because PHP (sometimes value is 12.300000000000001)
        self::assertStringStartsWith($expectedValue, $paramInfo->getDefaultValueAsString());
    }

    public function testGetClassForTypeHintedMethodParameters()
    {
        $content = '<?php class Foo { public function myMethod($untyped, array $array, \stdClass $object) {} }';

        $reflector = new ClassReflector(new AggregateSourceLocator([
            new PhpInternalSourceLocator(),
            new StringSourceLocator($content),
        ]));
        $classInfo = $reflector->reflect('Foo');
        $methodInfo = $classInfo->getMethod('myMethod');

        self::assertNull($methodInfo->getParameter('untyped')->getClass());
        self::assertNull($methodInfo->getParameter('array')->getClass());

        $hintedClassReflection = $methodInfo->getParameter('object')->getClass();
        self::assertInstanceOf(ReflectionClass::class, $hintedClassReflection);
        self::assertSame('stdClass', $hintedClassReflection->getName());
    }

    public function testCannotClone()
    {
        $classInfo = $this->reflector->reflect(Methods::class);
        $methodInfo = $classInfo->getMethod('methodWithParameters');
        $paramInfo = $methodInfo->getParameter('parameter1');

        $this->expectException(Uncloneable::class);
        $unused = clone $paramInfo;
    }

    public function testGetClassFromSelfTypeHintedProperty()
    {
        $content = '<?php class Foo { public function myMethod(self $param) {} }';

        $reflector = new ClassReflector(new AggregateSourceLocator([
            new StringSourceLocator($content),
        ]));
        $classInfo = $reflector->reflect('Foo');
        $methodInfo = $classInfo->getMethod('myMethod');

        $hintedClassReflection = $methodInfo->getParameter('param')->getClass();
        self::assertInstanceOf(ReflectionClass::class, $hintedClassReflection);
        self::assertSame('Foo', $hintedClassReflection->getName());
    }

    public function testGetClassFromParentTypeHintedProperty()
    {
        $content = '<?php class Foo extends \stdClass { public function myMethod(parent $param) {} }';

        $reflector = new ClassReflector(new AggregateSourceLocator([
            new PhpInternalSourceLocator(),
            new StringSourceLocator($content),
        ]));
        $classInfo = $reflector->reflect('Foo');
        $methodInfo = $classInfo->getMethod('myMethod');

        $hintedClassReflection = $methodInfo->getParameter('param')->getClass();
        self::assertInstanceOf(ReflectionClass::class, $hintedClassReflection);
        self::assertSame('stdClass', $hintedClassReflection->getName());
    }
}
