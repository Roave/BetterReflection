<?php

namespace BetterReflectionTest\Reflection;

use BetterReflection\Reflection\Exception\Uncloneable;
use BetterReflection\Reflection\ReflectionClass;
use BetterReflection\Reflection\ReflectionParameter;
use BetterReflection\Reflector\ClassReflector;
use BetterReflection\Reflector\FunctionReflector;
use BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use BetterReflection\SourceLocator\Type\PhpInternalSourceLocator;
use phpDocumentor\Reflection\Types;
use BetterReflection\SourceLocator\Type\ComposerSourceLocator;
use BetterReflection\SourceLocator\Type\StringSourceLocator;

/**
 * @covers \BetterReflection\Reflection\ReflectionParameter
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

        $this->assertInstanceOf(ReflectionParameter::class, $parameterInfo);
        $this->assertSame('index', $parameterInfo->getName());
    }

    public function testCreateFromClassInstanceAndMethod()
    {
        $parameterInfo = ReflectionParameter::createFromClassInstanceAndMethod(new \SplDoublyLinkedList(), 'add', 'index');

        $this->assertInstanceOf(ReflectionParameter::class, $parameterInfo);
        $this->assertSame('index', $parameterInfo->getName());
    }

    public function testCreateFromSpecWithArray()
    {
        $parameterInfo = ReflectionParameter::createFromSpec([\SplDoublyLinkedList::class, 'add'], 'index');

        $this->assertInstanceOf(ReflectionParameter::class, $parameterInfo);
        $this->assertSame('index', $parameterInfo->getName());
    }

    public function testCreateFromSpecWithArrayWithInstance()
    {
        $splDoublyLinkedList = new \SplDoublyLinkedList();
        $parameterInfo = ReflectionParameter::createFromSpec([$splDoublyLinkedList, 'add'], 'index');

        $this->assertInstanceOf(ReflectionParameter::class, $parameterInfo);
        $this->assertSame('index', $parameterInfo->getName());
    }

    public function testCreateFromSpecWithFunctionName()
    {
        require_once __DIR__ . '/../Fixture/ClassForHinting.php';
        $parameterInfo = ReflectionParameter::createFromSpec('BetterReflectionTest\Fixture\testFunction', 'param1');

        $this->assertInstanceOf(ReflectionParameter::class, $parameterInfo);
        $this->assertSame('param1', $parameterInfo->getName());
    }

    public function testCreateFromSpecWithClosure()
    {
        $this->setExpectedException(\Exception::class, 'Creating by closure is not supported yet');
        ReflectionParameter::createFromSpec(function ($a) {}, 'a');
    }

    public function testCreateFromSpecWithInvalidArgumentThrowsException()
    {
        $this->setExpectedException(\InvalidArgumentException::class, 'Could not create reflection from the spec given');
        ReflectionParameter::createFromSpec(123, 'a');
    }

    public function testImplementsReflector()
    {
        $classInfo = $this->reflector->reflect('\BetterReflectionTest\Fixture\Methods');
        $methodInfo = $classInfo->getMethod('methodWithParameters');
        $paramInfo = $methodInfo->getParameter('parameter1');

        $this->assertInstanceOf(\Reflector::class, $paramInfo);
    }

    public function testExportThrowsException()
    {
        $this->setExpectedException(\Exception::class);
        ReflectionParameter::export();
    }

    /**
     * @return array
     */
    public function defaultParameterProvider()
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
    public function testDefaultParametersTypes($defaultExpression, $expectedValue)
    {
        $content = "<?php class Foo { public function myMethod(\$var = $defaultExpression) {} }";

        $reflector = new ClassReflector(new StringSourceLocator($content));
        $classInfo = $reflector->reflect('Foo');
        $methodInfo = $classInfo->getMethod('myMethod');
        $paramInfo = $methodInfo->getParameter('var');
        $actualValue = $paramInfo->getDefaultValue();

        $this->assertSame($expectedValue, $actualValue);
    }

    public function testGetDefaultValueWhenDefaultValueNotAvailableThrowsException()
    {
        $content = "<?php class Foo { public function myMethod(\$var) {} }";

        $reflector = new ClassReflector(new StringSourceLocator($content));
        $classInfo = $reflector->reflect('Foo');
        $methodInfo = $classInfo->getMethod('myMethod');
        $paramInfo = $methodInfo->getParameter('var');

        $this->setExpectedException(\LogicException::class, 'This parameter does not have a default value available');
        $paramInfo->getDefaultValue();
    }

    public function testGetDocBlockTypeStrings()
    {
        $classInfo = $this->reflector->reflect('\BetterReflectionTest\Fixture\Methods');

        $method = $classInfo->getMethod('methodWithParameters');

        $param1 = $method->getParameter('parameter1');
        $this->assertSame(['string'], $param1->getDocBlockTypeStrings());

        $param2 = $method->getParameter('parameter2');
        $this->assertSame(['int', 'float'], $param2->getDocBlockTypeStrings());
    }

    public function testGetDocBlockTypes()
    {
        $classInfo = $this->reflector->reflect('\BetterReflectionTest\Fixture\Methods');

        $method = $classInfo->getMethod('methodWithParameters');

        $param1 = $method->getParameter('parameter1');
        $param1Types = $param1->getDocBlockTypes();
        $this->assertCount(1, $param1Types);
        $this->assertInstanceOf(Types\String_::class, $param1Types[0]);

        $param2 = $method->getParameter('parameter2');
        $param2Types = $param2->getDocBlockTypes();
        $this->assertCount(2, $param2Types);
        $this->assertInstanceOf(Types\Integer::class, $param2Types[0]);
        $this->assertInstanceOf(Types\Float_::class, $param2Types[1]);
    }

    public function testStringCast()
    {
        $classInfo = $this->reflector->reflect('\BetterReflectionTest\Fixture\Methods');
        $method = $classInfo->getMethod('methodWithOptionalParameters');

        $requiredParam = $method->getParameter('parameter');
        $this->assertSame('Parameter #0 [ <required> $parameter ]', (string)$requiredParam);

        $optionalParam = $method->getParameter('optionalParameter');
        $this->assertSame('Parameter #1 [ <optional> $optionalParameter = NULL ]', (string)$optionalParam);
    }

    public function testGetPosition()
    {
        $classInfo = $this->reflector->reflect('\BetterReflectionTest\Fixture\Methods');

        $method = $classInfo->getMethod('methodWithParameters');

        $param1 = $method->getParameter('parameter1');
        $this->assertSame(0, $param1->getPosition());

        $param2 = $method->getParameter('parameter2');
        $this->assertSame(1, $param2->getPosition());
    }

    /**
     * @return array
     */
    public function typeHintProvider()
    {
        return [
            ['stdClassParameter', Types\Object_::class, '\stdClass', 'stdClass'],
            ['fullyQualifiedClassParameter', Types\Object_::class, '\BetterReflectionTest\Fixture\ClassForHinting', 'ClassForHinting'],
            ['arrayParameter', Types\Array_::class],
            ['callableParameter', Types\Callable_::class],
            ['namespaceClassParameter', Types\Object_::class, '\BetterReflectionTest\Fixture\ClassForHinting', 'ClassForHinting'],
        ];
    }

    /**
     * @dataProvider typeHintProvider
     * @param string $parameterToTest
     * @param string $expectedType
     * @param string|null $expectedFqsen
     * @param string|null $expectedFqsenName
     */
    public function testGetTypeHint($parameterToTest, $expectedType, $expectedFqsen = null, $expectedFqsenName = null)
    {
        $classInfo = $this->reflector->reflect('\BetterReflectionTest\Fixture\Methods');

        $method = $classInfo->getMethod('methodWithExplicitTypedParameters');

        $type = $method->getParameter($parameterToTest)->getTypeHint();
        $this->assertInstanceOf($expectedType, $type);

        if (null !== $expectedFqsen) {
            $this->assertSame($expectedFqsen, (string)$type->getFqsen());
        }

        if (null !== $expectedFqsenName) {
            $this->assertSame($expectedFqsenName, $type->getFqsen()->getName());
        }
    }

    public function testIsCallable()
    {
        $classInfo = $this->reflector->reflect('\BetterReflectionTest\Fixture\Methods');

        $method = $classInfo->getMethod('methodWithExplicitTypedParameters');

        $nonCallableParam = $method->getParameter('stdClassParameter');
        $this->assertFalse($nonCallableParam->isCallable());

        $callableParam = $method->getParameter('callableParameter');
        $this->assertTrue($callableParam->isCallable());
    }

    public function testIsArray()
    {
        $classInfo = $this->reflector->reflect('\BetterReflectionTest\Fixture\Methods');

        $method = $classInfo->getMethod('methodWithExplicitTypedParameters');

        $nonArrayParam = $method->getParameter('stdClassParameter');
        $this->assertFalse($nonArrayParam->isArray());

        $arrayParam = $method->getParameter('arrayParameter');
        $this->assertTrue($arrayParam->isArray());
    }

    public function testIsVariadic()
    {
        $classInfo = $this->reflector->reflect('\BetterReflectionTest\Fixture\Methods');

        $method = $classInfo->getMethod('methodWithVariadic');

        $nonVariadicParam = $method->getParameter('nonVariadicParameter');
        $this->assertFalse($nonVariadicParam->isVariadic());

        $variadicParam = $method->getParameter('variadicParameter');
        $this->assertTrue($variadicParam->isVariadic());
    }

    public function testIsPassedByReference()
    {
        $classInfo = $this->reflector->reflect('\BetterReflectionTest\Fixture\Methods');

        $method = $classInfo->getMethod('methodWithReference');

        $nonRefParam = $method->getParameter('nonRefParameter');
        $this->assertFalse($nonRefParam->isPassedByReference());
        $this->assertTrue($nonRefParam->canBePassedByValue());

        $refParam = $method->getParameter('refParameter');
        $this->assertTrue($refParam->isPassedByReference());
        $this->assertFalse($refParam->canBePassedByValue());
    }

    public function testGetDefaultValueAndIsOptional()
    {
        $classInfo = $this->reflector->reflect('\BetterReflectionTest\Fixture\Methods');
        $method = $classInfo->getMethod('methodWithNonOptionalDefaultValue');

        $firstParam = $method->getParameter('firstParameter');
        $this->assertFalse($firstParam->isOptional());
        $this->assertTrue($firstParam->isDefaultValueAvailable());

        $secondParam = $method->getParameter('secondParameter');
        $this->assertFalse($secondParam->isOptional());
        $this->assertFalse($secondParam->isDefaultValueAvailable());
    }

    /**
     * @group 109
     */
    public function testVariadicParametersAreAlsoImplicitlyOptional()
    {
        $classInfo = $this->reflector->reflect('\BetterReflectionTest\Fixture\Methods');

        $method = $classInfo->getMethod('methodWithVariadic');

        $nonVariadicParam = $method->getParameter('nonVariadicParameter');
        $this->assertFalse($nonVariadicParam->isVariadic());
        $this->assertFalse($nonVariadicParam->isOptional());

        $variadicParam = $method->getParameter('variadicParameter');
        $this->assertTrue($variadicParam->isVariadic());
        $this->assertTrue($variadicParam->isOptional());
    }

    public function testAllowsNull()
    {
        $classInfo = $this->reflector->reflect('\BetterReflectionTest\Fixture\Methods');
        $method = $classInfo->getMethod('methodToCheckAllowsNull');

        $firstParam = $method->getParameter('allowsNull');
        $this->assertTrue($firstParam->allowsNull());

        $secondParam = $method->getParameter('hintDisallowNull');
        $this->assertFalse($secondParam->allowsNull());

        $thirdParam = $method->getParameter('hintAllowNull');
        $this->assertTrue($thirdParam->allowsNull());
    }

    public function testIsDefaultValueConstantAndGetDefaultValueConstantName()
    {
        $classInfo = $this->reflector->reflect('\BetterReflectionTest\Fixture\Methods');
        $method = $classInfo->getMethod('methodWithConstAsDefault');

        $constDefault = $method->getParameter('constDefault');
        $this->assertTrue($constDefault->isDefaultValueConstant());
        $this->assertSame('SOME_CONST', $constDefault->getDefaultValueConstantName());

        $definedDefault = $method->getParameter('definedDefault');
        $this->assertTrue($definedDefault->isDefaultValueConstant());
        $this->assertSame('SOME_DEFINED_VALUE', $definedDefault->getDefaultValueConstantName());

        $intDefault = $method->getParameter('intDefault');
        $this->assertFalse($intDefault->isDefaultValueConstant());

        $this->setExpectedException(\LogicException::class, 'This parameter is not a constant default value, so cannot have a constant name');
        $intDefault->getDefaultValueConstantName();
    }

    public function testGetDeclaringFunction()
    {
        $content = "<?php class Foo { public function myMethod(\$var = 123) {} }";

        $reflector = new ClassReflector(new StringSourceLocator($content));
        $classInfo = $reflector->reflect('Foo');
        $methodInfo = $classInfo->getMethod('myMethod');
        $paramInfo = $methodInfo->getParameter('var');

        $this->assertSame($methodInfo, $paramInfo->getDeclaringFunction());
    }

    public function testGetDeclaringClassForMethod()
    {
        $content = "<?php class Foo { public function myMethod(\$var = 123) {} }";

        $reflector = new ClassReflector(new StringSourceLocator($content));
        $classInfo = $reflector->reflect('Foo');
        $methodInfo = $classInfo->getMethod('myMethod');
        $paramInfo = $methodInfo->getParameter('var');

        $this->assertSame($classInfo, $paramInfo->getDeclaringClass());
    }

    public function testGetDeclaringClassForFunctionReturnsNull()
    {
        $content = "<?php function myMethod(\$var = 123) {}";

        $reflector = new FunctionReflector(new StringSourceLocator($content));
        $functionInfo = $reflector->reflect('myMethod');
        $paramInfo = $functionInfo->getParameter('var');

        $this->assertNull($paramInfo->getDeclaringClass());
    }

    public function defaultValueStringProvider()
    {
        return [
            ['123', '123'],
            ['12.3', '12.300000000000001'], // Oh, yes, because PHP.
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
     * @dataProvider defaultValueStringProvider
     */
    public function testGetDefaultValueAsString($defaultValue, $expectedValue)
    {
        $content = "<?php function myMethod(\$var = $defaultValue) {}";

        $reflector = new FunctionReflector(new StringSourceLocator($content));
        $functionInfo = $reflector->reflect('myMethod');
        $paramInfo = $functionInfo->getParameter('var');

        $this->assertSame($expectedValue, $paramInfo->getDefaultValueAsString());
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

        $this->assertNull($methodInfo->getParameter('untyped')->getClass());
        $this->assertNull($methodInfo->getParameter('array')->getClass());

        $hintedClassReflection = $methodInfo->getParameter('object')->getClass();
        $this->assertInstanceOf(ReflectionClass::class, $hintedClassReflection);
        $this->assertSame('stdClass', $hintedClassReflection->getName());
    }

    public function testCannotClone()
    {
        $classInfo = $this->reflector->reflect('\BetterReflectionTest\Fixture\Methods');
        $methodInfo = $classInfo->getMethod('methodWithParameters');
        $paramInfo = $methodInfo->getParameter('parameter1');

        $this->setExpectedException(Uncloneable::class);
        $unused = clone $paramInfo;
    }

    /**
     * @group 107
     */
    public function testVariadicParametersAreAlsoImplicitlyOptional()
    {
        $classInfo = $this->reflector->reflect('\BetterReflectionTest\Fixture\Methods');

        $method = $classInfo->getMethod('methodWithVariadic');

        $nonVariadicParam = $method->getParameter('nonVariadicParameter');
        $this->assertFalse($nonVariadicParam->isVariadic());
        $this->assertFalse($nonVariadicParam->isOptional());

        $variadicParam = $method->getParameter('variadicParameter');
        $this->assertTrue($variadicParam->isVariadic());
        $this->assertTrue($variadicParam->isOptional());
    }
}
