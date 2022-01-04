<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection;

use BackedEnum;
use Bar;
use Baz;
use E;
use Iterator;
use OutOfBoundsException;
use Php4StyleCaseInsensitiveConstruct;
use Php4StyleConstruct;
use PhpParser\Node\Stmt\Class_;
use PHPUnit\Framework\TestCase;
use Qux;
use Reflection as CoreReflection;
use ReflectionClass as CoreReflectionClass;
use ReflectionMethod as CoreReflectionMethod;
use ReflectionProperty as CoreReflectionProperty;
use Roave\BetterReflection\Reflection\Exception\NotAClassReflection;
use Roave\BetterReflection\Reflection\Exception\NotAnInterfaceReflection;
use Roave\BetterReflection\Reflection\Exception\PropertyDoesNotExist;
use Roave\BetterReflection\Reflection\Exception\Uncloneable;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionClassConstant;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use Roave\BetterReflection\Reflection\ReflectionNamedType;
use Roave\BetterReflection\Reflection\ReflectionParameter;
use Roave\BetterReflection\Reflection\ReflectionProperty;
use Roave\BetterReflection\Reflection\ReflectionUnionType;
use Roave\BetterReflection\Reflector\DefaultReflector;
use Roave\BetterReflection\Reflector\Exception\IdentifierNotFound;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\Located\LocatedSource;
use Roave\BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\ComposerSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\MemoizingSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;
use Roave\BetterReflectionTest\BetterReflectionSingleton;
use Roave\BetterReflectionTest\ClassesImplementingIterators;
use Roave\BetterReflectionTest\ClassesWithCloneMethod;
use Roave\BetterReflectionTest\ClassesWithPublicOrNonPublicConstructor\ClassWithExtendedConstructor;
use Roave\BetterReflectionTest\ClassesWithPublicOrNonPublicConstructor\ClassWithoutConstructor;
use Roave\BetterReflectionTest\ClassesWithPublicOrNonPublicConstructor\ClassWithPrivateConstructor;
use Roave\BetterReflectionTest\ClassesWithPublicOrNonPublicConstructor\ClassWithProtectedConstructor;
use Roave\BetterReflectionTest\ClassesWithPublicOrNonPublicConstructor\ClassWithPublicConstructor;
use Roave\BetterReflectionTest\ClassWithInterfaces;
use Roave\BetterReflectionTest\ClassWithInterfacesExtendingInterfaces;
use Roave\BetterReflectionTest\ClassWithInterfacesOther;
use Roave\BetterReflectionTest\Fixture;
use Roave\BetterReflectionTest\Fixture\AbstractClass;
use Roave\BetterReflectionTest\Fixture\Attr;
use Roave\BetterReflectionTest\Fixture\ClassExtendingNonAbstractClass;
use Roave\BetterReflectionTest\Fixture\ClassForHinting;
use Roave\BetterReflectionTest\Fixture\ClassUsesAndRenamesMethodFromTrait;
use Roave\BetterReflectionTest\Fixture\ClassUsesTwoTraitsWithSameMethodNameOneIsAbstract;
use Roave\BetterReflectionTest\Fixture\ClassUsingTraitWithAbstractMethod;
use Roave\BetterReflectionTest\Fixture\ClassWithAttributes;
use Roave\BetterReflectionTest\Fixture\ClassWithCaseInsensitiveMethods;
use Roave\BetterReflectionTest\Fixture\ClassWithMissingParent;
use Roave\BetterReflectionTest\Fixture\DefaultProperties;
use Roave\BetterReflectionTest\Fixture\ExampleClass;
use Roave\BetterReflectionTest\Fixture\ExampleClassWhereConstructorIsNotFirstMethod;
use Roave\BetterReflectionTest\Fixture\ExampleInterface;
use Roave\BetterReflectionTest\Fixture\ExampleTrait;
use Roave\BetterReflectionTest\Fixture\FinalClass;
use Roave\BetterReflectionTest\Fixture\IntEnum;
use Roave\BetterReflectionTest\Fixture\InterfaceForEnum;
use Roave\BetterReflectionTest\Fixture\InvalidInheritances;
use Roave\BetterReflectionTest\Fixture\MethodsOrder;
use Roave\BetterReflectionTest\Fixture\PureEnum;
use Roave\BetterReflectionTest\Fixture\StaticProperties;
use Roave\BetterReflectionTest\Fixture\StaticPropertyGetSet;
use Roave\BetterReflectionTest\Fixture\StringEnum;
use Roave\BetterReflectionTest\Fixture\UpperCaseConstructDestruct;
use Roave\BetterReflectionTest\FixtureOther\AnotherClass;
use stdClass;
use Stringable;
use TypeError;
use UnitEnum;

use function array_keys;
use function array_map;
use function array_walk;
use function basename;
use function class_exists;
use function count;
use function file_get_contents;
use function sort;
use function sprintf;
use function uniqid;

/**
 * @covers \Roave\BetterReflection\Reflection\ReflectionClass
 */
class ReflectionClassTest extends TestCase
{
    private Locator $astLocator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->astLocator = BetterReflectionSingleton::instance()->astLocator();
    }

    private function getComposerLocator(): ComposerSourceLocator
    {
        return new ComposerSourceLocator($GLOBALS['loader'], $this->astLocator);
    }

    public function testCanReflectInternalClassWithDefaultLocator(): void
    {
        self::assertSame(stdClass::class, ReflectionClass::createFromName(stdClass::class)->getName());
    }

    public function testCanReflectInstance(): void
    {
        $instance = new stdClass();
        self::assertSame(stdClass::class, ReflectionClass::createFromInstance($instance)->getName());
    }

    public function testCanReflectEvaledClassWithDefaultLocator(): void
    {
        $className = uniqid('foo', false);

        eval('class ' . $className . '{}');

        self::assertSame($className, ReflectionClass::createFromName($className)->getName());
    }

    public function testClassNameMethodsWithNamespace(): void
    {
        $reflector = new DefaultReflector($this->getComposerLocator());
        $classInfo = $reflector->reflectClass(ExampleClass::class);

        self::assertTrue($classInfo->inNamespace());
        self::assertSame(ExampleClass::class, $classInfo->getName());
        self::assertSame('Roave\BetterReflectionTest\Fixture', $classInfo->getNamespaceName());
        self::assertSame('ExampleClass', $classInfo->getShortName());
    }

    public function testClassNameMethodsWithoutNamespace(): void
    {
        $classInfo = (new DefaultReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/NoNamespace.php',
            $this->astLocator,
        )))->reflectClass('ClassWithNoNamespace');

        self::assertFalse($classInfo->inNamespace());
        self::assertSame('ClassWithNoNamespace', $classInfo->getName());
        self::assertSame('', $classInfo->getNamespaceName());
        self::assertSame('ClassWithNoNamespace', $classInfo->getShortName());
    }

    public function testClassNameMethodsWithExplicitGlobalNamespace(): void
    {
        $classInfo = (new DefaultReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/ExampleClass.php',
            $this->astLocator,
        )))->reflectClass('ClassWithExplicitGlobalNamespace');

        self::assertFalse($classInfo->inNamespace());
        self::assertSame('ClassWithExplicitGlobalNamespace', $classInfo->getName());
        self::assertSame('', $classInfo->getNamespaceName());
        self::assertSame('ClassWithExplicitGlobalNamespace', $classInfo->getShortName());
    }

    /**
     * @coversNothing
     */
    public function testReflectingAClassDoesNotLoadTheClass(): void
    {
        self::assertFalse(class_exists(ExampleClass::class, false));

        $reflector = new DefaultReflector($this->getComposerLocator());
        $reflector->reflectClass(ExampleClass::class);

        self::assertFalse(class_exists(ExampleClass::class, false));
    }

    public function testGetMethods(): void
    {
        $reflector = new DefaultReflector($this->getComposerLocator());
        $classInfo = $reflector->reflectClass(ExampleClass::class);
        self::assertGreaterThanOrEqual(1, $classInfo->getMethods());
    }

    public function testGetMethodsForPureEnum(): void
    {
        $reflector = new DefaultReflector(new AggregateSourceLocator([
            new SingleFileSourceLocator(__DIR__ . '/../Fixture/Enums.php', $this->astLocator),
            BetterReflectionSingleton::instance()->sourceLocator(),
        ]));

        $classInfo = $reflector->reflectClass(PureEnum::class);
        $methods   = $classInfo->getImmediateMethods();

        self::assertCount(1, $methods);
        self::assertArrayHasKey('cases', $methods);

        $method = $methods['cases'];

        self::assertTrue($method->isPublic());
        self::assertTrue($method->isStatic());
        self::assertSame(0, $method->getNumberOfParameters());

        $returnType = $method->getReturnType();

        self::assertInstanceOf(ReflectionNamedType::class, $returnType);
        self::assertSame('array', $returnType->__toString());
    }

    public function testGetMethodsForBackedEnum(): void
    {
        $reflector = new DefaultReflector(new AggregateSourceLocator([
            new SingleFileSourceLocator(__DIR__ . '/../Fixture/Enums.php', $this->astLocator),
            BetterReflectionSingleton::instance()->sourceLocator(),
        ]));

        $classInfo = $reflector->reflectClass(StringEnum::class);
        $methods   = $classInfo->getImmediateMethods();

        self::assertCount(3, $methods);
    }

    public function dataMethodsOfBackedEnum(): array
    {
        return [
            [
                'cases',
                [],
                ReflectionNamedType::class,
                'array',
            ],
            [
                'from',
                ['value' => [ReflectionUnionType::class, 'string|int']],
                ReflectionNamedType::class,
                'static',
            ],
            [
                'tryFrom',
                ['value' => [ReflectionUnionType::class, 'string|int']],
                ReflectionUnionType::class,
                'static|null',
            ],
        ];
    }

    /**
     * @param list<array{0: class-string, 1: string}> $parameters
     *
     * @dataProvider dataMethodsOfBackedEnum
     */
    public function testMethodsOfBackedEnum(string $methodName, array $parameters, string $returnTypeClass, string $returnType): void
    {
        $reflector = new DefaultReflector(new AggregateSourceLocator([
            new SingleFileSourceLocator(__DIR__ . '/../Fixture/Enums.php', $this->astLocator),
            BetterReflectionSingleton::instance()->sourceLocator(),
        ]));

        $classInfo = $reflector->reflectClass(StringEnum::class);

        self::assertTrue($classInfo->hasMethod($methodName));

        $method = $classInfo->getMethod($methodName);

        self::assertTrue($method->isPublic(), $methodName);
        self::assertTrue($method->isStatic(), $methodName);
        self::assertSame(count($parameters), $method->getNumberOfParameters(), $methodName);

        foreach ($parameters as $parameterName => $parameterData) {
            $parameter = $method->getParameter($parameterName);

            self::assertInstanceOf(ReflectionParameter::class, $parameter);

            $parameterType = $parameter->getType();

            self::assertInstanceOf($parameterData[0], $parameterType);
            self::assertSame($parameterData[1], $parameterType->__toString());
        }

        $methodReturnType = $method->getReturnType();

        self::assertInstanceOf($returnTypeClass, $methodReturnType);
        self::assertSame($returnType, $methodReturnType->__toString());
    }

    public function getMethodsWithFilterDataProvider(): array
    {
        return [
            [CoreReflectionMethod::IS_STATIC, 1],
            [CoreReflectionMethod::IS_ABSTRACT, 1],
            [CoreReflectionMethod::IS_FINAL, 1],
            [CoreReflectionMethod::IS_PUBLIC, 19],
            [CoreReflectionMethod::IS_PROTECTED, 1],
            [CoreReflectionMethod::IS_PRIVATE, 1],
            [
                CoreReflectionMethod::IS_STATIC |
                CoreReflectionMethod::IS_ABSTRACT |
                CoreReflectionMethod::IS_FINAL |
                CoreReflectionMethod::IS_PUBLIC |
                CoreReflectionMethod::IS_PROTECTED |
                CoreReflectionMethod::IS_PRIVATE,
                21,
            ],
        ];
    }

    /**
     * @dataProvider getMethodsWithFilterDataProvider
     */
    public function testGetMethodsWithFilter(int $filter, int $count): void
    {
        $reflector = new DefaultReflector($this->getComposerLocator());
        $classInfo = $reflector->reflectClass(Fixture\Methods::class);

        self::assertCount($count, $classInfo->getMethods($filter));
        self::assertCount($count, $classInfo->getImmediateMethods($filter));
    }

    public function testCaseInsensitiveMethods(): void
    {
        $classInfo = (new DefaultReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/ClassWithCaseInsensitiveMethods.php',
            $this->astLocator,
        )))->reflectClass(ClassWithCaseInsensitiveMethods::class);

        self::assertCount(1, $classInfo->getMethods());
    }

    public function testGetMethodsReturnsInheritedMethods(): void
    {
        $classInfo = (new DefaultReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/InheritedClassMethods.php',
            $this->astLocator,
        )))->reflectClass('Qux');

        $methods = $classInfo->getMethods();
        self::assertCount(6, $methods);
        self::assertContainsOnlyInstancesOf(ReflectionMethod::class, $methods);

        self::assertSame('a', $classInfo->getMethod('a')->getName(), 'Failed asserting that method a from interface Foo was returned');
        self::assertSame('Foo', $classInfo->getMethod('a')->getDeclaringClass()->getName());

        self::assertSame('b', $classInfo->getMethod('b')->getName(), 'Failed asserting that method b from trait Bar was returned');
        self::assertSame('Bar', $classInfo->getMethod('b')->getDeclaringClass()->getName());

        self::assertSame('c', $classInfo->getMethod('c')->getName(), 'Failed asserting that public method c from parent class Baz was returned');
        self::assertSame('Baz', $classInfo->getMethod('c')->getDeclaringClass()->getName());

        self::assertSame('d', $classInfo->getMethod('d')->getName(), 'Failed asserting that protected method d from parent class Baz was returned');
        self::assertSame('Baz', $classInfo->getMethod('d')->getDeclaringClass()->getName());

        self::assertSame('e', $classInfo->getMethod('e')->getName(), 'Failed asserting that private method e from parent class Baz was returned');
        self::assertSame('Baz', $classInfo->getMethod('e')->getDeclaringClass()->getName());

        self::assertSame('f', $classInfo->getMethod('f')->getName(), 'Failed asserting that method from SUT was returned');
        self::assertSame('Qux', $classInfo->getMethod('f')->getDeclaringClass()->getName());
    }

    public function testGetMethodsWithBrokenClass(): void
    {
        $classInfo = (new DefaultReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/ClassWithMissingParent.php',
            $this->astLocator,
        )))->reflectClass(ClassWithMissingParent::class);

        try {
            $classInfo->getMethods();
        } catch (IdentifierNotFound) {
            // Ignore error for the first time
        }

        self::expectException(IdentifierNotFound::class);

        $classInfo->getMethods();
    }

    public function testGetMethodsOrder(): void
    {
        $classInfo = (new DefaultReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/MethodsOrder.php',
            $this->astLocator,
        )))->reflectClass(MethodsOrder::class);

        $actualMethodNames = array_map(static fn (ReflectionMethod $method): string => $method->getName(), $classInfo->getMethods());

        $expectedMethodNames = [
            'f1',
            'f2',
            'f3',
            'f4',
            'f5',
            'f6',
            'f7',
            'f8',
            'f9',
            'f10',
        ];

        self::assertSame($expectedMethodNames, $actualMethodNames);
    }

    public function testGetImmediateMethods(): void
    {
        $classInfo = (new DefaultReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/InheritedClassMethods.php',
            $this->astLocator,
        )))->reflectClass('Qux');

        $methods = $classInfo->getImmediateMethods();

        self::assertCount(1, $methods);
        self::assertInstanceOf(ReflectionMethod::class, $methods['f']);
        self::assertSame('f', $methods['f']->getName());
    }

    public function testGetConstants(): void
    {
        $reflector = new DefaultReflector($this->getComposerLocator());
        $classInfo = $reflector->reflectClass(ExampleClass::class);
        self::assertSame([
            'MY_CONST_1' => 123,
            'MY_CONST_2' => 234,
            'MY_CONST_3' => 345,
            'MY_CONST_4' => 456,
            'MY_CONST_5' => 567,
            'MY_CONST_6' => 678,
        ], $classInfo->getConstants());
    }

    public function testGetConstant(): void
    {
        $reflector = new DefaultReflector($this->getComposerLocator());
        $classInfo = $reflector->reflectClass(ExampleClass::class);
        self::assertSame(123, $classInfo->getConstant('MY_CONST_1'));
        self::assertSame(234, $classInfo->getConstant('MY_CONST_2'));
        self::assertSame(345, $classInfo->getConstant('MY_CONST_3'));
        self::assertSame(456, $classInfo->getConstant('MY_CONST_4'));
        self::assertSame(567, $classInfo->getConstant('MY_CONST_5'));
        self::assertNull($classInfo->getConstant('NON_EXISTENT_CONSTANT'));
    }

    public function testGetReflectionConstants(): void
    {
        $reflector = new DefaultReflector($this->getComposerLocator());
        $classInfo = $reflector->reflectClass(ExampleClass::class);
        self::assertCount(6, $classInfo->getReflectionConstants());
    }

    public function testGetReflectionConstant(): void
    {
        $reflector = new DefaultReflector($this->getComposerLocator());
        $classInfo = $reflector->reflectClass(ExampleClass::class);
        self::assertSame(123, $classInfo->getReflectionConstant('MY_CONST_1')->getValue());
        self::assertSame(234, $classInfo->getReflectionConstant('MY_CONST_2')->getValue());
        self::assertSame(345, $classInfo->getReflectionConstant('MY_CONST_3')->getValue());
        self::assertSame(456, $classInfo->getReflectionConstant('MY_CONST_4')->getValue());
        self::assertSame(567, $classInfo->getReflectionConstant('MY_CONST_5')->getValue());
        self::assertSame(678, $classInfo->getReflectionConstant('MY_CONST_6')->getValue());
        self::assertNull($classInfo->getConstant('NON_EXISTENT_CONSTANT'));
    }

    public function testGetConstructor(): void
    {
        $reflector   = new DefaultReflector($this->getComposerLocator());
        $classInfo   = $reflector->reflectClass(ExampleClass::class);
        $constructor = $classInfo->getConstructor();

        self::assertInstanceOf(ReflectionMethod::class, $constructor);
        self::assertTrue($constructor->isConstructor());
    }

    public function testGetConstructorThatIsNotFirstMethod(): void
    {
        $reflector   = (new DefaultReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/ExampleClass.php',
            $this->astLocator,
        )));
        $classInfo   = $reflector->reflectClass(ExampleClassWhereConstructorIsNotFirstMethod::class);
        $constructor = $classInfo->getConstructor();

        self::assertInstanceOf(ReflectionMethod::class, $constructor);
        self::assertTrue($constructor->isConstructor());
    }

    public function testGetCaseInsensitiveConstructor(): void
    {
        $reflector   = new DefaultReflector($this->getComposerLocator());
        $classInfo   = $reflector->reflectClass(UpperCaseConstructDestruct::class);
        $constructor = $classInfo->getConstructor();

        self::assertInstanceOf(ReflectionMethod::class, $constructor);
        self::assertTrue($constructor->isConstructor());
    }

    public function testGetConstructorWhenPhp4Style(): void
    {
        $classInfo = (new DefaultReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/Php4StyleConstruct.php',
            $this->astLocator,
        )))->reflectClass(Php4StyleConstruct::class);

        $constructor = $classInfo->getConstructor();

        self::assertInstanceOf(ReflectionMethod::class, $constructor);
        self::assertTrue($constructor->isConstructor());
    }

    public function testGetConstructorWhenPhp4StyleInNamespace(): void
    {
        $this->expectException(OutOfBoundsException::class);

        $classInfo = (new DefaultReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/Php4StyleConstructInNamespace.php',
            $this->astLocator,
        )))->reflectClass(Fixture\Php4StyleConstructInNamespace::class);

        $classInfo->getConstructor();
    }

    public function testGetConstructorWhenPhp4StyleCaseInsensitive(): void
    {
        $classInfo = (new DefaultReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/Php4StyleCaseInsensitiveConstruct.php',
            $this->astLocator,
        )))->reflectClass(Php4StyleCaseInsensitiveConstruct::class);

        $constructor = $classInfo->getConstructor();

        self::assertInstanceOf(ReflectionMethod::class, $constructor);
        self::assertTrue($constructor->isConstructor());
    }

    public function testGetProperties(): void
    {
        $reflector = new DefaultReflector($this->getComposerLocator());
        $classInfo = $reflector->reflectClass(ExampleClass::class);

        $properties = $classInfo->getProperties();

        self::assertContainsOnlyInstancesOf(ReflectionProperty::class, $properties);
        self::assertCount(6, $properties);
    }

    public function testGetPropertiesForPureEnum(): void
    {
        $reflector = new DefaultReflector(new AggregateSourceLocator([
            new SingleFileSourceLocator(__DIR__ . '/../Fixture/Enums.php', $this->astLocator),
            BetterReflectionSingleton::instance()->sourceLocator(),
        ]));

        $classInfo  = $reflector->reflectClass(PureEnum::class);
        $properties = $classInfo->getImmediateProperties();

        self::assertArrayHasKey('name', $properties);

        $property = $properties['name'];

        self::assertTrue($property->isPublic());
        self::assertTrue($property->isReadOnly());
        self::assertFalse($property->isPromoted());
        self::assertTrue($property->isDefault());
        self::assertSame(0, $property->getPositionInAst());
    }

    public function dataGetPropertiesForBackedEnum(): array
    {
        return [
            [
                StringEnum::class,
                ['name' => 'string', 'value' => 'string'],
            ],
            [
                IntEnum::class,
                ['name' => 'string', 'value' => 'int'],
            ],
        ];
    }

    /**
     * @param array<string, string> $propertiesData
     *
     * @dataProvider dataGetPropertiesForBackedEnum
     */
    public function testGetPropertiesForBackedEnum(string $className, array $propertiesData): void
    {
        $reflector = new DefaultReflector(new AggregateSourceLocator([
            new SingleFileSourceLocator(__DIR__ . '/../Fixture/Enums.php', $this->astLocator),
            BetterReflectionSingleton::instance()->sourceLocator(),
        ]));

        $classInfo  = $reflector->reflectClass($className);
        $properties = $classInfo->getImmediateProperties();

        foreach ($propertiesData as $propertyName => $propertyType) {
            $fullPropertyName = sprintf('%s::$%s', $className, $propertyName);

            self::assertArrayHasKey($propertyName, $properties, $fullPropertyName);

            $property = $properties[$propertyName];

            self::assertTrue($property->isPublic(), $fullPropertyName);
            self::assertTrue($property->isReadOnly(), $fullPropertyName);
            self::assertFalse($property->isPromoted(), $fullPropertyName);
            self::assertTrue($property->isDefault(), $fullPropertyName);
            self::assertSame(0, $property->getPositionInAst(), $fullPropertyName);
            self::assertSame($propertyType, $property->getType()->__toString(), $fullPropertyName);
        }
    }

    public function testGetPropertiesDeclaredWithOneKeyword(): void
    {
        $php = <<<'PHP'
<?php
class Foo
{
    public $a = 0,
           $b = 1;
    protected $c = 'c',
              $d = 'd';
    private $e = bool,
            $f = false;
}
PHP;

        $expectedPropertiesNames = ['a', 'b', 'c', 'd', 'e', 'f'];

        $classInfo = (new DefaultReflector(new StringSourceLocator($php, $this->astLocator)))->reflectClass('Foo');

        $properties = $classInfo->getProperties();

        self::assertCount(6, $properties);
        self::assertSame($expectedPropertiesNames, array_keys($properties));
    }

    public function getPropertiesWithFilterDataProvider(): array
    {
        return [
            [CoreReflectionProperty::IS_STATIC, 1],
            [CoreReflectionProperty::IS_PUBLIC, 3],
            [CoreReflectionProperty::IS_PROTECTED, 1],
            [CoreReflectionProperty::IS_PRIVATE, 2],
            [
                CoreReflectionProperty::IS_STATIC |
                CoreReflectionProperty::IS_PUBLIC |
                CoreReflectionProperty::IS_PROTECTED |
                CoreReflectionProperty::IS_PRIVATE,
                6,
            ],
        ];
    }

    /**
     * @dataProvider getPropertiesWithFilterDataProvider
     */
    public function testGetPropertiesWithFilter(int $filter, int $count): void
    {
        $reflector = new DefaultReflector($this->getComposerLocator());
        $classInfo = $reflector->reflectClass(ExampleClass::class);

        self::assertCount($count, $classInfo->getProperties($filter));
        self::assertCount($count, $classInfo->getImmediateProperties($filter));
    }

    public function testGetPropertiesReturnsInheritedProperties(): void
    {
        $classInfo = (new DefaultReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/InheritedClassProperties.php',
            $this->astLocator,
        )))->reflectClass(Qux::class);

        $properties = $classInfo->getProperties();
        self::assertCount(6, $properties);
        self::assertContainsOnlyInstancesOf(ReflectionProperty::class, $properties);

        self::assertSame('a', $classInfo->getProperty('a')->getName(), 'Failed asserting that property a from trait Bar was returned');
        self::assertSame(Bar::class, $classInfo->getProperty('a')->getDeclaringClass()->getName());

        self::assertSame('b', $classInfo->getProperty('b')->getName(), 'Failed asserting that private property b from trait Bar was returned');
        self::assertSame(Bar::class, $classInfo->getProperty('b')->getDeclaringClass()->getName());

        self::assertSame('c', $classInfo->getProperty('c')->getName(), 'Failed asserting that public property c from parent class Baz was returned');
        self::assertSame(Baz::class, $classInfo->getProperty('c')->getDeclaringClass()->getName());

        self::assertSame('d', $classInfo->getProperty('d')->getName(), 'Failed asserting that protected property d from parent class Baz was returned');
        self::assertSame(Baz::class, $classInfo->getProperty('d')->getDeclaringClass()->getName());

        self::assertSame('f', $classInfo->getProperty('f')->getName(), 'Failed asserting that property f from Qux was returned');
        self::assertSame(Qux::class, $classInfo->getProperty('f')->getDeclaringClass()->getName());

        self::assertSame('g', $classInfo->getProperty('g')->getName(), 'Failed asserting that property g from Qux was returned');
        self::assertSame(Qux::class, $classInfo->getProperty('g')->getDeclaringClass()->getName());
    }

    public function testGetImmediateProperties(): void
    {
        $classInfo = (new DefaultReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/InheritedClassProperties.php',
            $this->astLocator,
        )))->reflectClass(Qux::class);

        $properties = $classInfo->getImmediateProperties();
        self::assertCount(2, $properties);
        self::assertContainsOnlyInstancesOf(ReflectionProperty::class, $properties);

        $fProperty = $classInfo->getProperty('f');

        self::assertSame(Qux::class, $fProperty->getDeclaringClass()->getName());
        self::assertFalse($fProperty->isPromoted());

        $gProperty = $classInfo->getProperty('g');

        self::assertSame(Qux::class, $gProperty->getDeclaringClass()->getName());
        self::assertTrue($gProperty->isPromoted());
    }

    public function testGetProperty(): void
    {
        $reflector = new DefaultReflector($this->getComposerLocator());
        $classInfo = $reflector->reflectClass(ExampleClass::class);

        self::assertNull($classInfo->getProperty('aNonExistentProperty'));

        $property = $classInfo->getProperty('publicProperty');

        self::assertInstanceOf(ReflectionProperty::class, $property);
        self::assertSame('publicProperty', $property->getName());
        self::assertStringEndsWith('test/unit/Fixture', $property->getDefaultValue());
    }

    public function testGetFileName(): void
    {
        $reflector = new DefaultReflector($this->getComposerLocator());
        $classInfo = $reflector->reflectClass(ExampleClass::class);

        $detectedFilename = $classInfo->getFileName();

        self::assertSame('ExampleClass.php', basename($detectedFilename));
    }

    public function testGetLocatedSource(): void
    {
        $node          = new Class_('SomeClass');
        $locatedSource = new LocatedSource('<?php class SomeClass {}', 'SomeClass');
        $reflector     = new DefaultReflector(new StringSourceLocator('<?php', $this->astLocator));
        $reflection    = ReflectionClass::createFromNode($reflector, $node, $locatedSource);

        self::assertSame($locatedSource, $reflection->getLocatedSource());
    }

    public function testStaticCreation(): void
    {
        $reflection = ReflectionClass::createFromName(ExampleClass::class);
        self::assertSame('ExampleClass', $reflection->getShortName());
    }

    public function testGetParentClassDefault(): void
    {
        $childReflection = (new DefaultReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/ExampleClass.php',
            $this->astLocator,
        )))->reflectClass(Fixture\ClassWithParent::class);

        $parentReflection = $childReflection->getParentClass();
        self::assertSame('ExampleClass', $parentReflection->getShortName());
    }

    public function testGetParentClassThrowsExceptionWithNoParent(): void
    {
        $reflection = ReflectionClass::createFromName(ExampleClass::class);

        self::assertNull($reflection->getParentClass());
    }

    public function testGetParentClassNames(): void
    {
        $childReflection = (new DefaultReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/ExampleClass.php',
            $this->astLocator,
        )))->reflectClass(Fixture\ClassWithTwoParents::class);

        self::assertSame(['Roave\\BetterReflectionTest\\Fixture\\ClassWithParent', 'Roave\\BetterReflectionTest\\Fixture\\ExampleClass'], $childReflection->getParentClassNames());
    }

    public function startEndLineProvider(): array
    {
        return [
            ["<?php\n\nclass Foo {\n}\n", 3, 4],
            ["<?php\n\nclass Foo {\n\n}\n", 3, 5],
            ["<?php\n\n\nclass Foo {\n}\n", 4, 5],
        ];
    }

    /**
     * @dataProvider startEndLineProvider
     */
    public function testStartEndLine(string $php, int $expectedStart, int $expectedEnd): void
    {
        $reflector = new DefaultReflector(new StringSourceLocator($php, $this->astLocator));
        $classInfo = $reflector->reflectClass('Foo');

        self::assertSame($expectedStart, $classInfo->getStartLine());
        self::assertSame($expectedEnd, $classInfo->getEndLine());
    }

    public function columnsProvider(): array
    {
        return [
            ["<?php\n\nclass Foo {\n}\n", 1, 1],
            ["<?php\n\n    class Foo {\n    }\n", 5, 5],
            ['<?php class Foo { }', 7, 19],
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
        $classInfo = $reflector->reflectClass('Foo');

        self::assertSame($startColumn, $classInfo->getStartColumn());
        self::assertSame($endColumn, $classInfo->getEndColumn());
    }

    public function testGetDocComment(): void
    {
        $reflector = new DefaultReflector($this->getComposerLocator());
        $classInfo = $reflector->reflectClass(ExampleClass::class);

        self::assertStringContainsString('This class comment should be used.', $classInfo->getDocComment());
    }

    public function testGetDocCommentBetweenComments(): void
    {
        $php       = '<?php
            /* A comment */
            /** Class description */
            /* An another comment */
            class Bar implements Foo {}
        ';
        $reflector = (new DefaultReflector(new StringSourceLocator($php, $this->astLocator)))->reflectClass('Bar');

        self::assertStringContainsString('Class description', $reflector->getDocComment());
    }

    public function testGetDocCommentReturnsEmptyStringWithNoComment(): void
    {
        $classInfo = (new DefaultReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/ExampleClass.php',
            $this->astLocator,
        )))->reflectClass(AnotherClass::class);

        self::assertSame('', $classInfo->getDocComment());
    }

    public function testHasProperty(): void
    {
        $reflector = new DefaultReflector($this->getComposerLocator());
        $classInfo = $reflector->reflectClass(ExampleClass::class);

        self::assertFalse($classInfo->hasProperty('aNonExistentProperty'));
        self::assertTrue($classInfo->hasProperty('publicProperty'));
    }

    public function testHasConstant(): void
    {
        $reflector = new DefaultReflector($this->getComposerLocator());
        $classInfo = $reflector->reflectClass(ExampleClass::class);

        self::assertFalse($classInfo->hasConstant('NON_EXISTENT_CONSTANT'));
        self::assertTrue($classInfo->hasConstant('MY_CONST_1'));
    }

    public function testHasMethod(): void
    {
        $reflector = new DefaultReflector($this->getComposerLocator());
        $classInfo = $reflector->reflectClass(ExampleClass::class);

        self::assertFalse($classInfo->hasMethod('aNonExistentMethod'));
        self::assertTrue($classInfo->hasMethod('someMethod'));
    }

    public function testHasMethodIsCaseInsensitive(): void
    {
        $reflector = new DefaultReflector($this->getComposerLocator());
        $classInfo = $reflector->reflectClass(ExampleClass::class);

        self::assertTrue($classInfo->hasMethod('someMethod'));
        self::assertTrue($classInfo->hasMethod('SOMEMETHOD'));
        self::assertTrue($classInfo->hasMethod('somemethod'));
    }

    public function testGetMethodIsCaseInsensitive(): void
    {
        $reflector = new DefaultReflector($this->getComposerLocator());
        $classInfo = $reflector->reflectClass(ExampleClass::class);

        $method1 = $classInfo->getMethod('someMethod');
        $method2 = $classInfo->getMethod('SOMEMETHOD');

        self::assertSame($method1, $method2);
    }

    public function testGetDefaultProperties(): void
    {
        $reflector = new DefaultReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/DefaultProperties.php', $this->astLocator));
        $classInfo = $reflector->reflectClass(DefaultProperties::class);

        self::assertSame([
            'fromTrait' => 'anything',
            'hasDefault' => 'const',
            'hasNullAsDefault' => null,
            'noDefault' => null,
            'hasDefaultWithType' => 123,
            'hasNullAsDefaultWithType' => null,
            'noDefaultWithType' => null,
        ], $classInfo->getDefaultProperties());
    }

    public function testGetDefaultPropertiesShouldIgnoreRuntimeProperty(): void
    {
        $object                     = new DefaultProperties();
        $object->notDefaultProperty = null;

        $classInfo = ReflectionClass::createFromInstance($object);

        self::assertSame([
            'fromTrait' => 'anything',
            'hasDefault' => 'const',
            'hasNullAsDefault' => null,
            'noDefault' => null,
            'hasDefaultWithType' => 123,
            'hasNullAsDefaultWithType' => null,
            'noDefaultWithType' => null,
        ], $classInfo->getDefaultProperties());
    }

    public function testIsAnonymousWithNotAnonymousClass(): void
    {
        $classInfo = (new DefaultReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/ExampleClass.php',
            $this->astLocator,
        )))->reflectClass(ExampleClass::class);

        self::assertFalse($classInfo->isAnonymous());
    }

    public function testIsAnonymousWithAnonymousClassNoNamespace(): void
    {
        $reflector = new DefaultReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/AnonymousClassNoNamespace.php',
            $this->astLocator,
        ));

        $allClassesInfo = $reflector->reflectAllClasses();
        self::assertCount(1, $allClassesInfo);

        $classInfo = $allClassesInfo[0];
        self::assertTrue($classInfo->isAnonymous());
        self::assertFalse($classInfo->inNamespace());
        self::assertStringStartsWith(ReflectionClass::ANONYMOUS_CLASS_NAME_PREFIX, $classInfo->getName());
        self::assertStringEndsWith('Fixture/AnonymousClassNoNamespace.php(3)', $classInfo->getName());
    }

    public function testIsAnonymousWithAnonymousClassInNamespace(): void
    {
        $reflector = new DefaultReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/AnonymousClassInNamespace.php',
            $this->astLocator,
        ));

        $allClassesInfo = $reflector->reflectAllClasses();
        self::assertCount(2, $allClassesInfo);

        foreach ($allClassesInfo as $classInfo) {
            self::assertTrue($classInfo->isAnonymous());
            self::assertFalse($classInfo->inNamespace());
            self::assertStringStartsWith(ReflectionClass::ANONYMOUS_CLASS_NAME_PREFIX, $classInfo->getName());
            self::assertStringMatchesFormat('%sFixture/AnonymousClassInNamespace.php(%d)', $classInfo->getName());
        }
    }

    public function testIsAnonymousWithNestedAnonymousClasses(): void
    {
        $reflector = new DefaultReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/NestedAnonymousClassInstances.php',
            $this->astLocator,
        ));

        $allClassesInfo = $reflector->reflectAllClasses();
        self::assertCount(3, $allClassesInfo);

        foreach ($allClassesInfo as $classInfo) {
            self::assertTrue($classInfo->isAnonymous());
            self::assertFalse($classInfo->inNamespace());
            self::assertStringStartsWith(ReflectionClass::ANONYMOUS_CLASS_NAME_PREFIX, $classInfo->getName());
            self::assertStringMatchesFormat('%sFixture/NestedAnonymousClassInstances.php(%d)', $classInfo->getName());
        }
    }

    public function testAnonymousClassWithParent(): void
    {
        $reflector = new DefaultReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/AnonymousClassInstanceWithParent.php',
            $this->astLocator,
        ));

        $allClassesInfo = $reflector->reflectAllClasses();
        self::assertCount(3, $allClassesInfo);

        $classInfo = $allClassesInfo[2];

        self::assertTrue($classInfo->isAnonymous());
        self::assertFalse($classInfo->inNamespace());
        self::assertStringStartsWith(Fixture\FixtureParent::class, $classInfo->getName());

        $parent = $classInfo->getParentClass();
        self::assertNotNull($parent);
        self::assertSame(Fixture\FixtureParent::class, $parent->getName());
    }

    public function testAnonymousClassWithInterface(): void
    {
        $reflector = new DefaultReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/AnonymousClassInstanceWithInterface.php',
            $this->astLocator,
        ));

        $allClassesInfo = $reflector->reflectAllClasses();
        self::assertCount(3, $allClassesInfo);

        $classInfo = $allClassesInfo[2];

        self::assertTrue($classInfo->isAnonymous());
        self::assertFalse($classInfo->inNamespace());
        self::assertStringStartsWith(Fixture\FixtureInterface::class, $classInfo->getName());
        self::assertSame([Fixture\FixtureInterface::class, Fixture\FixtureSecondInterface::class], $classInfo->getInterfaceNames());
    }

    public function testIsAnonymousWithAnonymousClassInString(): void
    {
        $php = '<?php
            function createAnonymous()
            {
                return new class {};
            }
        ';

        $reflector = new DefaultReflector(new StringSourceLocator($php, $this->astLocator));

        $allClassesInfo = $reflector->reflectAllClasses();
        self::assertCount(1, $allClassesInfo);

        $classInfo = $allClassesInfo[0];
        self::assertTrue($classInfo->isAnonymous());
        self::assertFalse($classInfo->inNamespace());
        self::assertStringStartsWith(ReflectionClass::ANONYMOUS_CLASS_NAME_PREFIX, $classInfo->getName());
        self::assertStringEndsWith('(4)', $classInfo->getName());
    }

    public function testIsInternalWithUserDefinedClass(): void
    {
        $reflector = new DefaultReflector($this->getComposerLocator());
        $classInfo = $reflector->reflectClass(ExampleClass::class);

        self::assertFalse($classInfo->isInternal());
        self::assertTrue($classInfo->isUserDefined());
        self::assertNull($classInfo->getExtensionName());
    }

    public function testIsInternalWithInternalClass(): void
    {
        $classInfo = BetterReflectionSingleton::instance()->reflector()->reflectClass(stdClass::class);

        self::assertTrue($classInfo->isInternal());
        self::assertFalse($classInfo->isUserDefined());
        self::assertSame('Core', $classInfo->getExtensionName());
    }

    public function testIsAbstract(): void
    {
        $reflector = new DefaultReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/ExampleClass.php',
            $this->astLocator,
        ));

        $classInfo = $reflector->reflectClass(AbstractClass::class);
        self::assertTrue($classInfo->isAbstract());

        $classInfo = $reflector->reflectClass(ExampleClass::class);
        self::assertFalse($classInfo->isAbstract());
    }

    public function testIsFinal(): void
    {
        $reflector = new DefaultReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/ExampleClass.php',
            $this->astLocator,
        ));

        $classInfo = $reflector->reflectClass(FinalClass::class);
        self::assertTrue($classInfo->isFinal());

        $classInfo = $reflector->reflectClass(ExampleClass::class);
        self::assertFalse($classInfo->isFinal());
    }

    public function testIsFinalForEnum(): void
    {
        $reflector = new DefaultReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/Enums.php',
            $this->astLocator,
        ));

        $classInfo = $reflector->reflectClass(PureEnum::class);
        self::assertTrue($classInfo->isFinal());
    }

    public function modifierProvider(): array
    {
        return [
            ['ExampleClass', 0, []],
            ['AbstractClass', CoreReflectionClass::IS_EXPLICIT_ABSTRACT, ['abstract']],
            ['FinalClass', CoreReflectionClass::IS_FINAL, ['final']],
        ];
    }

    /**
     * @param list<string> $expectedModifierNames
     *
     * @dataProvider modifierProvider
     */
    public function testGetModifiers(string $className, int $expectedModifier, array $expectedModifierNames): void
    {
        $reflector = new DefaultReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/ExampleClass.php',
            $this->astLocator,
        ));

        $classInfo = $reflector->reflectClass('\Roave\BetterReflectionTest\Fixture\\' . $className);

        self::assertSame($expectedModifier, $classInfo->getModifiers());
        self::assertSame(
            $expectedModifierNames,
            CoreReflection::getModifierNames($classInfo->getModifiers()),
        );
    }

    public function testIsTrait(): void
    {
        $reflector = new DefaultReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/ExampleClass.php',
            $this->astLocator,
        ));

        $classInfo = $reflector->reflectClass(ExampleTrait::class);
        self::assertTrue($classInfo->isTrait());

        $classInfo = $reflector->reflectClass(ExampleClass::class);
        self::assertFalse($classInfo->isTrait());
    }

    public function testIsInterface(): void
    {
        $reflector = new DefaultReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/ExampleClass.php',
            $this->astLocator,
        ));

        $classInfo = $reflector->reflectClass(ExampleInterface::class);
        self::assertTrue($classInfo->isInterface());

        $classInfo = $reflector->reflectClass(ExampleClass::class);
        self::assertFalse($classInfo->isInterface());
    }

    public function testGetTraits(): void
    {
        $reflector = new DefaultReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/TraitFixture.php',
            $this->astLocator,
        ));

        $classInfo = $reflector->reflectClass('TraitFixtureA');
        $traits    = $classInfo->getTraits();

        self::assertCount(1, $traits);
        self::assertInstanceOf(ReflectionClass::class, $traits[0]);
        self::assertTrue($traits[0]->isTrait());
    }

    public function testGetDeclaringClassForTraits(): void
    {
        $reflector = new DefaultReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/TraitFixture.php',
            $this->astLocator,
        ));

        $classInfo = $reflector->reflectClass('TraitFixtureAA');

        self::assertTrue($classInfo->hasMethod('foo'));
        self::assertSame('TraitFixtureTraitA', $classInfo->getMethod('foo')->getDeclaringClass()->getName());
    }

    public function declaringClassProvider(): array
    {
        return [
            [
                ClassUsingTraitWithAbstractMethod::class,
                'foo',
                'AbstractClassImplementingMethodFromTrait',
                'AbstractClassImplementingMethodFromTrait',
                'ClassUsingTraitWithAbstractMethod',
            ],
            [
                ClassUsingTraitWithAbstractMethod::class,
                'bar',
                'TraitWithAbstractMethod',
                'ClassUsingTraitWithAbstractMethod',
                'ClassUsingTraitWithAbstractMethod',
            ],
            [
                ClassExtendingNonAbstractClass::class,
                'boo',
                'TraitWithBoo',
                'ClassExtendingNonAbstractClass',
                'ClassExtendingNonAbstractClass',
            ],
            [
                ClassUsesTwoTraitsWithSameMethodNameOneIsAbstract::class,
                'bar',
                'ImplementationTrait',
                'ClassUsesTwoTraitsWithSameMethodNameOneIsAbstract',
                'ClassUsesTwoTraitsWithSameMethodNameOneIsAbstract',
            ],
            [
                ClassUsesAndRenamesMethodFromTrait::class,
                'abstractMethod',
                'TraitWithNonAbstractMethod',
                'ClassUsesAndRenamesMethodFromTrait',
                'ClassUsesAndRenamesMethodFromTrait',
            ],
        ];
    }

    /**
     * @dataProvider declaringClassProvider
     */
    public function testGetDeclaringClassWithTraitAndParent(
        string $className,
        string $methodName,
        string $declaringClassShortName,
        string $implementingClassShortName,
        string $currentClassShortName,
    ): void {
        $reflector = new DefaultReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/TraitWithAbstractMethod.php',
            $this->astLocator,
        ));

        $classInfo = $reflector->reflectClass($className);

        self::assertTrue($classInfo->hasMethod($methodName));

        $fooMethodInfo = $classInfo->getMethod($methodName);

        self::assertSame($declaringClassShortName, $fooMethodInfo->getDeclaringClass()->getShortName());
        self::assertSame($implementingClassShortName, $fooMethodInfo->getImplementingClass()->getShortName());
        self::assertSame($currentClassShortName, $fooMethodInfo->getCurrentClass()->getShortName());
    }

    public function testGetTraitsReturnsEmptyArrayWhenNoTraitsUsed(): void
    {
        $reflector = new DefaultReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/TraitFixture.php',
            $this->astLocator,
        ));

        $classInfo = $reflector->reflectClass('TraitFixtureB');
        $traits    = $classInfo->getTraits();

        self::assertCount(0, $traits);
    }

    public function testGetTraitNames(): void
    {
        $reflector = new DefaultReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/TraitFixture.php',
            $this->astLocator,
        ));

        self::assertSame(
            ['TraitFixtureTraitA'],
            $reflector->reflectClass('TraitFixtureA')->getTraitNames(),
        );
    }

    public function testGetTraitAliases(): void
    {
        $reflector = new DefaultReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/TraitFixture.php',
            $this->astLocator,
        ));

        $classInfo = $reflector->reflectClass('TraitFixtureC');

        self::assertSame([
            'a_protected' => 'TraitFixtureTraitC::a',
            'b_renamed' => 'TraitFixtureTraitC::b',
            'd_renamed' => 'TraitFixtureTraitC3::d',
        ], $classInfo->getTraitAliases());
    }

    public function testMethodsFromTraits(): void
    {
        $reflector = new DefaultReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/TraitFixture.php',
            $this->astLocator,
        ));

        $classInfo = $reflector->reflectClass('TraitFixtureC');

        self::assertTrue($classInfo->hasMethod('a'));
        self::assertTrue($classInfo->hasMethod('a_protected'));

        $aProtected = $classInfo->getMethod('a_protected');

        self::assertSame('TraitFixtureTraitC', $aProtected->getDeclaringClass()->getName());

        self::assertTrue($classInfo->hasMethod('b'));
        self::assertTrue($classInfo->hasMethod('b_renamed'));

        $bRenamed = $classInfo->getMethod('b_renamed');

        self::assertSame('TraitFixtureTraitC', $bRenamed->getDeclaringClass()->getName());

        self::assertTrue($classInfo->hasMethod('c'));

        $c = $classInfo->getMethod('c');

        self::assertSame('c', $c->getName());
        self::assertSame('TraitFixtureTraitC', $c->getDeclaringClass()->getName());

        self::assertTrue($classInfo->hasMethod('d'));
        self::assertTrue($classInfo->hasMethod('d_renamed'));

        self::assertSame('TraitFixtureTraitC2', $classInfo->getMethod('d')->getDeclaringClass()->getName());
        self::assertSame('TraitFixtureTraitC2', $classInfo->getMethod('d_renamed')->getDeclaringClass()->getName());
    }

    public function testMethodsFromTraitsWithConflicts(): void
    {
        $reflector = new DefaultReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/TraitFixture.php',
            $this->astLocator,
        ));

        $classInfo = $reflector->reflectClass('TraitFixtureD');

        self::assertTrue($classInfo->hasMethod('boo'));
        self::assertSame('TraitFixtureD', $classInfo->getMethod('boo')->getDeclaringClass()->getName());

        self::assertTrue($classInfo->hasMethod('foo'));
        self::assertSame('TraitFixtureTraitD1', $classInfo->getMethod('foo')->getDeclaringClass()->getName());

        $foo = $classInfo->getMethod('foo');

        self::assertSame('TraitFixtureTraitD1', $foo->getDeclaringClass()->getName());
        self::assertSame('TraitFixtureD', $foo->getImplementingClass()->getName());

        self::assertTrue($classInfo->hasMethod('hoo'));
        self::assertTrue($classInfo->hasMethod('hooFirstAlias'));
        self::assertTrue($classInfo->hasMethod('hooSecondAlias'));

        $hooFirstAlias  = $classInfo->getMethod('hooFirstAlias');
        $hooSecondAlias = $classInfo->getMethod('hooSecondAlias');

        self::assertSame('TraitFixtureTraitD1', $hooFirstAlias->getDeclaringClass()->getName());
        self::assertSame('TraitFixtureTraitD1', $hooSecondAlias->getDeclaringClass()->getName());
        self::assertSame('TraitFixtureD', $hooFirstAlias->getImplementingClass()->getName());
        self::assertSame('TraitFixtureD', $hooSecondAlias->getImplementingClass()->getName());
    }

    public function testMethodsFromTraitsWithAliases(): void
    {
        $reflector = new DefaultReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/TraitFixture.php',
            $this->astLocator,
        ));

        $classInfo = $reflector->reflectClass('TraitFixtureE');

        self::assertTrue($classInfo->hasMethod('foo'));
        self::assertSame('foo', $classInfo->getMethod('foo')->getName());
        self::assertTrue($classInfo->hasMethod('parentFoo'));
        self::assertSame('parentFoo', $classInfo->getMethod('parentFoo')->getName());

        $traitInfo = $reflector->reflectClass('SecondTraitForFixtureE');

        self::assertTrue($traitInfo->hasMethod('foo'));
        self::assertSame('foo', $traitInfo->getMethod('foo')->getName());
        self::assertTrue($traitInfo->hasMethod('parentFoo'));
        self::assertSame('parentFoo', $traitInfo->getMethod('parentFoo')->getName());
    }

    public function testMethodsFromTraitsWithAliasesAndConflicts(): void
    {
        $reflector = new DefaultReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/TraitFixture.php',
            $this->astLocator,
        ));

        $classInfo = $reflector->reflectClass('TraitFixtureF');

        self::assertTrue($classInfo->hasMethod('a'));
        self::assertTrue($classInfo->hasMethod('aliasedA'));

        $a        = $classInfo->getMethod('a');
        $aliasedA = $classInfo->getMethod('aliasedA');

        self::assertSame('FirstTraitForFixtureF', $a->getDeclaringClass()->getName());
        self::assertSame('SecondTraitForFixtureF', $aliasedA->getDeclaringClass()->getName());

        self::assertTrue($classInfo->hasMethod('b'));
        self::assertTrue($classInfo->hasMethod('aliasedB'));

        $b        = $classInfo->getMethod('b');
        $aliasedB = $classInfo->getMethod('aliasedB');

        self::assertSame('SecondTraitForFixtureF', $b->getDeclaringClass()->getName());
        self::assertSame('FirstTraitForFixtureF', $aliasedB->getDeclaringClass()->getName());
    }

    public function testGetInterfaceNames(): void
    {
        $reflector = new DefaultReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/ClassWithInterfaces.php',
            $this->astLocator,
        ));

        self::assertSame(
            [
                ClassWithInterfaces\A::class,
                ClassWithInterfacesOther\B::class,
                ClassWithInterfaces\C::class,
                ClassWithInterfacesOther\D::class,
                E::class,
            ],
            $reflector
                ->reflectClass(ClassWithInterfaces\ExampleClass::class)
                ->getInterfaceNames(),
            'Interfaces are retrieved in the correct numeric order (indexed by number)',
        );
    }

    public function testGetInterfacesForPureEnum(): void
    {
        $reflector = new DefaultReflector(new AggregateSourceLocator([
            new SingleFileSourceLocator(__DIR__ . '/../Fixture/Enums.php', $this->astLocator),
            BetterReflectionSingleton::instance()->sourceLocator(),
        ]));

        $classInfo = $reflector->reflectClass(PureEnum::class);

        self::assertSame([InterfaceForEnum::class, UnitEnum::class], $classInfo->getInterfaceNames());
        self::assertArrayHasKey(UnitEnum::class, $classInfo->getImmediateInterfaces());
    }

    public function testGetInterfaceNamesForBackedEnum(): void
    {
        $reflector = new DefaultReflector(new AggregateSourceLocator([
            new SingleFileSourceLocator(__DIR__ . '/../Fixture/Enums.php', $this->astLocator),
            BetterReflectionSingleton::instance()->sourceLocator(),
        ]));

        $classInfo = $reflector->reflectClass(StringEnum::class);

        self::assertSame([InterfaceForEnum::class, UnitEnum::class, BackedEnum::class], $classInfo->getInterfaceNames());
        self::assertArrayHasKey(UnitEnum::class, $classInfo->getImmediateInterfaces());
        self::assertArrayHasKey(BackedEnum::class, $classInfo->getImmediateInterfaces());
    }

    public function testGetInterfaces(): void
    {
        $reflector  = new DefaultReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/ClassWithInterfaces.php',
            $this->astLocator,
        ));
        $interfaces = $reflector
                ->reflectClass(ClassWithInterfaces\ExampleClass::class)
                ->getInterfaces();

        $expectedInterfaces = [
            ClassWithInterfaces\A::class,
            ClassWithInterfacesOther\B::class,
            ClassWithInterfaces\C::class,
            ClassWithInterfacesOther\D::class,
            E::class,
        ];

        self::assertCount(count($expectedInterfaces), $interfaces);

        foreach ($expectedInterfaces as $expectedInterface) {
            self::assertArrayHasKey($expectedInterface, $interfaces);
            self::assertInstanceOf(ReflectionClass::class, $interfaces[$expectedInterface]);
            self::assertSame($expectedInterface, $interfaces[$expectedInterface]->getName());
        }
    }

    public function testGetInterfaceNamesWillReturnAllInheritedInterfaceImplementationsOnASubclass(): void
    {
        $reflector = new DefaultReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/ClassWithInterfaces.php',
            $this->astLocator,
        ));

        self::assertSame(
            [
                ClassWithInterfaces\A::class,
                ClassWithInterfacesOther\B::class,
                ClassWithInterfaces\C::class,
                ClassWithInterfacesOther\D::class,
                E::class,
            ],
            $reflector
                ->reflectClass(ClassWithInterfaces\SubExampleClass::class)
                ->getInterfaceNames(),
            'Child class interfaces are retrieved in the correct numeric order (indexed by number)',
        );
    }

    public function testGetInterfacesWillReturnAllInheritedInterfaceImplementationsOnASubclass(): void
    {
        $reflector = new DefaultReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/ClassWithInterfaces.php',
            $this->astLocator,
        ));

        $interfaces = $reflector
            ->reflectClass(ClassWithInterfaces\SubExampleClass::class)
            ->getInterfaces();

        $expectedInterfaces = [
            ClassWithInterfaces\A::class,
            ClassWithInterfacesOther\B::class,
            ClassWithInterfaces\C::class,
            ClassWithInterfacesOther\D::class,
            E::class,
        ];

        self::assertCount(count($expectedInterfaces), $interfaces);

        foreach ($expectedInterfaces as $expectedInterface) {
            self::assertArrayHasKey($expectedInterface, $interfaces);
            self::assertInstanceOf(ReflectionClass::class, $interfaces[$expectedInterface]);
            self::assertSame($expectedInterface, $interfaces[$expectedInterface]->getName());
        }
    }

    public function testGetInterfaceNamesWillConsiderMultipleInheritanceLevelsAndImplementsOrderOverrides(): void
    {
        $reflector = new DefaultReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/ClassWithInterfaces.php',
            $this->astLocator,
        ));

        self::assertSame(
            [
                ClassWithInterfaces\A::class,
                ClassWithInterfacesOther\B::class,
                ClassWithInterfaces\C::class,
                ClassWithInterfacesOther\D::class,
                E::class,
                ClassWithInterfaces\B::class,
            ],
            $reflector
                ->reflectClass(ClassWithInterfaces\SubSubExampleClass::class)
                ->getInterfaceNames(),
            'Child class interfaces are retrieved in the correct numeric order (indexed by number)',
        );
    }

    public function testGetInterfacesWillConsiderMultipleInheritanceLevels(): void
    {
        $reflector  = new DefaultReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/ClassWithInterfaces.php',
            $this->astLocator,
        ));
        $interfaces = $reflector
            ->reflectClass(ClassWithInterfaces\SubSubExampleClass::class)
            ->getInterfaces();

        $expectedInterfaces = [
            ClassWithInterfaces\A::class,
            ClassWithInterfacesOther\B::class,
            ClassWithInterfaces\C::class,
            ClassWithInterfacesOther\D::class,
            E::class,
            ClassWithInterfaces\B::class,
        ];

        self::assertCount(count($expectedInterfaces), $interfaces);

        foreach ($expectedInterfaces as $expectedInterface) {
            self::assertArrayHasKey($expectedInterface, $interfaces);
            self::assertInstanceOf(ReflectionClass::class, $interfaces[$expectedInterface]);
            self::assertSame($expectedInterface, $interfaces[$expectedInterface]->getName());
        }
    }

    public function testGetInterfacesWillConsiderInterfaceInheritanceLevels(): void
    {
        $reflector  = new DefaultReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/ClassWithInterfaces.php',
            $this->astLocator,
        ));
        $interfaces = $reflector
            ->reflectClass(ClassWithInterfaces\ExampleImplementingCompositeInterface::class)
            ->getInterfaces();

        $expectedInterfaces = [
            ClassWithInterfacesExtendingInterfaces\D::class,
            ClassWithInterfacesExtendingInterfaces\C::class,
            ClassWithInterfacesExtendingInterfaces\B::class,
            ClassWithInterfacesExtendingInterfaces\A::class,
        ];

        self::assertCount(count($expectedInterfaces), $interfaces);

        foreach ($expectedInterfaces as $expectedInterface) {
            self::assertArrayHasKey($expectedInterface, $interfaces);
            self::assertInstanceOf(ReflectionClass::class, $interfaces[$expectedInterface]);
            self::assertSame($expectedInterface, $interfaces[$expectedInterface]->getName());
        }
    }

    public function testIsInstance(): void
    {
        // note: ClassForHinting is safe to type-check against, as it will actually be loaded at runtime
        $class = (new DefaultReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/ClassForHinting.php',
            $this->astLocator,
        )))->reflectClass(ClassForHinting::class);

        self::assertFalse($class->isInstance(new stdClass()));
        self::assertFalse($class->isInstance($this));
        self::assertTrue($class->isInstance(new ClassForHinting()));

        $this->expectException(TypeError::class);

        $class->isInstance('foo');
    }

    public function testIsSubclassOf(): void
    {
        $subExampleClass = (new DefaultReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/ClassWithInterfaces.php',
            $this->astLocator,
        )))->reflectClass(ClassWithInterfaces\SubExampleClass::class);

        self::assertFalse(
            $subExampleClass->isSubclassOf(ClassWithInterfaces\SubExampleClass::class),
            'Not a subclass of itself',
        );
        self::assertFalse(
            $subExampleClass->isSubclassOf(ClassWithInterfaces\SubSubExampleClass::class),
            'Not a subclass of a child class',
        );
        self::assertFalse(
            $subExampleClass->isSubclassOf(stdClass::class),
            'Not a subclass of a unrelated',
        );
        self::assertTrue(
            $subExampleClass->isSubclassOf(ClassWithInterfaces\ExampleClass::class),
            'A subclass of a parent class',
        );
        self::assertTrue(
            $subExampleClass->isSubclassOf('\\' . ClassWithInterfaces\ExampleClass::class),
            'A subclass of a parent class (considering eventual backslashes upfront)',
        );
    }

    public function testImplementsInterface(): void
    {
        $subExampleClass = (new DefaultReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/ClassWithInterfaces.php',
            $this->astLocator,
        )))->reflectClass(ClassWithInterfaces\SubExampleClass::class);

        self::assertTrue($subExampleClass->implementsInterface(ClassWithInterfaces\A::class));
        self::assertFalse($subExampleClass->implementsInterface(ClassWithInterfaces\B::class));
        self::assertTrue($subExampleClass->implementsInterface(ClassWithInterfacesOther\B::class));
        self::assertTrue($subExampleClass->implementsInterface(ClassWithInterfaces\C::class));
        self::assertTrue($subExampleClass->implementsInterface(ClassWithInterfacesOther\D::class));
        self::assertTrue($subExampleClass->implementsInterface(E::class));
        self::assertTrue($subExampleClass->implementsInterface('\E'));
        self::assertFalse($subExampleClass->implementsInterface(Iterator::class));
        self::assertFalse($subExampleClass->implementsInterface('\Iterator'));
    }

    public function testIsInstantiable(): void
    {
        $reflector = new DefaultReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/ExampleClass.php',
            $this->astLocator,
        ));

        self::assertTrue($reflector->reflectClass(ExampleClass::class)->isInstantiable());
        self::assertTrue($reflector->reflectClass(Fixture\ClassWithParent::class)->isInstantiable());
        self::assertTrue($reflector->reflectClass(FinalClass::class)->isInstantiable());
        self::assertFalse($reflector->reflectClass(ExampleTrait::class)->isInstantiable());
        self::assertFalse($reflector->reflectClass(AbstractClass::class)->isInstantiable());
        self::assertFalse($reflector->reflectClass(ExampleInterface::class)->isInstantiable());

        $reflector = new DefaultReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/ClassesWithPublicOrNonPublicConstructor.php',
            $this->astLocator,
        ));

        self::assertTrue($reflector->reflectClass(ClassWithPublicConstructor::class)->isInstantiable());
        self::assertTrue($reflector->reflectClass(ClassWithoutConstructor::class)->isInstantiable());
        self::assertFalse($reflector->reflectClass(ClassWithPrivateConstructor::class)->isInstantiable());
        self::assertFalse($reflector->reflectClass(ClassWithProtectedConstructor::class)->isInstantiable());
        self::assertFalse($reflector->reflectClass(ClassWithExtendedConstructor::class)->isInstantiable());
    }

    public function testIsCloneable(): void
    {
        $reflector = new DefaultReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/ExampleClass.php',
            $this->astLocator,
        ));

        self::assertTrue($reflector->reflectClass(ExampleClass::class)->isCloneable());
        self::assertTrue($reflector->reflectClass(Fixture\ClassWithParent::class)->isCloneable());
        self::assertTrue($reflector->reflectClass(FinalClass::class)->isCloneable());
        self::assertFalse($reflector->reflectClass(ExampleTrait::class)->isCloneable());
        self::assertFalse($reflector->reflectClass(AbstractClass::class)->isCloneable());
        self::assertFalse($reflector->reflectClass(ExampleInterface::class)->isCloneable());

        $reflector = new DefaultReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/ClassesWithCloneMethod.php',
            $this->astLocator,
        ));

        self::assertTrue($reflector->reflectClass(ClassesWithCloneMethod\WithPublicClone::class)->isCloneable());
        self::assertFalse($reflector->reflectClass(ClassesWithCloneMethod\WithProtectedClone::class)->isCloneable());
        self::assertFalse($reflector->reflectClass(ClassesWithCloneMethod\WithPrivateClone::class)->isCloneable());
    }

    public function testIsIterateable(): void
    {
        $reflector = new DefaultReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/ClassesImplementingIterators.php',
            $this->astLocator,
        ));

        self::assertTrue(
            $reflector
                ->reflectClass(ClassesImplementingIterators\TraversableImplementation::class)
                ->isIterateable(),
        );
        self::assertFalse(
            $reflector
                ->reflectClass(ClassesImplementingIterators\NonTraversableImplementation::class)
                ->isIterateable(),
        );
        self::assertFalse(
            $reflector
                ->reflectClass(ClassesImplementingIterators\AbstractTraversableImplementation::class)
                ->isIterateable(),
        );
        self::assertFalse(
            $reflector
                ->reflectClass(ClassesImplementingIterators\TraversableExtension::class)
                ->isIterateable(),
        );
    }

    public function testGetParentClassesFailsWithClassExtendingFromInterface(): void
    {
        $reflector = new DefaultReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/InvalidInheritances.php',
            $this->astLocator,
        ));

        $class = $reflector->reflectClass(InvalidInheritances\ClassExtendingInterface::class);

        $this->expectException(NotAClassReflection::class);

        $class->getParentClass();
    }

    public function testGetParentClassesFailsWithClassExtendingFromTrait(): void
    {
        $reflector = new DefaultReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/InvalidInheritances.php',
            $this->astLocator,
        ));

        $class = $reflector->reflectClass(InvalidInheritances\ClassExtendingTrait::class);

        $this->expectException(NotAClassReflection::class);

        $class->getParentClass();
    }

    public function testGetInterfacesFailsWithInterfaceExtendingFromClass(): void
    {
        $reflector = new DefaultReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/InvalidInheritances.php',
            $this->astLocator,
        ));

        $class = $reflector->reflectClass(InvalidInheritances\InterfaceExtendingClass::class);

        $this->expectException(NotAnInterfaceReflection::class);

        $class->getInterfaces();
    }

    public function testGetInterfacesFailsWithInterfaceExtendingFromTrait(): void
    {
        $reflector = new DefaultReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/InvalidInheritances.php',
            $this->astLocator,
        ));

        $class = $reflector->reflectClass(InvalidInheritances\InterfaceExtendingTrait::class);

        $this->expectException(NotAnInterfaceReflection::class);

        $class->getInterfaces();
    }

    public function testGetImmediateInterfaces(): void
    {
        $reflector = new DefaultReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/PrototypeTree.php',
            $this->astLocator,
        ));

        $interfaces = $reflector->reflectClass('Boom\B')->getImmediateInterfaces();

        self::assertCount(1, $interfaces);
        self::assertInstanceOf(ReflectionClass::class, $interfaces['Boom\Boo']);
        self::assertSame('Boom\Boo', $interfaces['Boom\Boo']->getName());
    }

    public function testGetImmediateInterfacesDoesNotIncludeCurrentInterface(): void
    {
        $reflector = new DefaultReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/ClassWithInterfaces.php',
            $this->astLocator,
        ));

        $cInterfaces = array_map(
            static fn (ReflectionClass $interface): string => $interface->getShortName(),
            $reflector->reflectClass(ClassWithInterfacesExtendingInterfaces\C::class)->getImmediateInterfaces(),
        );
        $dInterfaces = array_map(
            static fn (ReflectionClass $interface): string => $interface->getShortName(),
            $reflector->reflectClass(ClassWithInterfacesExtendingInterfaces\D::class)->getImmediateInterfaces(),
        );

        sort($cInterfaces);
        sort($dInterfaces);

        self::assertSame(['B'], $cInterfaces);
        self::assertSame(['A', 'C'], $dInterfaces);
    }

    public function testReflectedTraitHasNoInterfaces(): void
    {
        $reflector = new DefaultReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/TraitFixture.php',
            $this->astLocator,
        ));

        $traitReflection = $reflector->reflectClass('TraitFixtureTraitA');
        self::assertSame([], $traitReflection->getImmediateInterfaces());
        self::assertSame([], $traitReflection->getInterfaces());
    }

    public function testToString(): void
    {
        $reflection = ReflectionClass::createFromName(ExampleClass::class);
        self::assertStringMatchesFormat(
            file_get_contents(__DIR__ . '/../Fixture/ExampleClassExport.txt'),
            $reflection->__toString(),
        );
    }

    public function testCannotClone(): void
    {
        $reflector = new DefaultReflector($this->getComposerLocator());
        $classInfo = $reflector->reflectClass(ExampleClass::class);

        $this->expectException(Uncloneable::class);
        clone $classInfo;
    }

    public function testGetStaticProperties(): void
    {
        $staticPropertiesFixtureFile = __DIR__ . '/../Fixture/StaticProperties.php';
        require_once $staticPropertiesFixtureFile;

        $classInfo = (new DefaultReflector(new SingleFileSourceLocator($staticPropertiesFixtureFile, $this->astLocator)))
            ->reflectClass(StaticProperties::class);

        $expectedStaticProperties = [
            'parentBaz' => 'parentBaz',
            'parentBat' => 456,
            'baz' => 'baz',
            'bat' => 123,
            'qux' => null,
        ];

        self::assertSame($expectedStaticProperties, $classInfo->getStaticProperties());
    }

    public function testGetStaticPropertyValue(): void
    {
        $staticPropertyGetSetFixtureFile = __DIR__ . '/../Fixture/StaticPropertyGetSet.php';
        require_once $staticPropertyGetSetFixtureFile;

        $classInfo = (new DefaultReflector(new SingleFileSourceLocator($staticPropertyGetSetFixtureFile, $this->astLocator)))
            ->reflectClass(StaticPropertyGetSet::class);

        self::assertSame('bazbaz', $classInfo->getStaticPropertyValue('baz'));
    }

    public function testGetStaticPropertyValueThrowsExceptionWhenPropertyDoesNotExist(): void
    {
        $staticPropertyGetSetFixtureFile = __DIR__ . '/../Fixture/StaticPropertyGetSet.php';
        require_once $staticPropertyGetSetFixtureFile;

        $classInfo = (new DefaultReflector(new SingleFileSourceLocator($staticPropertyGetSetFixtureFile, $this->astLocator)))
            ->reflectClass(StaticPropertyGetSet::class);

        $this->expectException(PropertyDoesNotExist::class);
        $classInfo->getStaticPropertyValue('foo');
    }

    /**
     * @runInSeparateProcess
     */
    public function testSetStaticPropertyValue(): void
    {
        $staticPropertyGetSetFixtureFile = __DIR__ . '/../Fixture/StaticPropertyGetSet.php';
        require_once $staticPropertyGetSetFixtureFile;

        $classInfo = (new DefaultReflector(new SingleFileSourceLocator($staticPropertyGetSetFixtureFile, $this->astLocator)))
            ->reflectClass(StaticPropertyGetSet::class);

        self::assertNull($classInfo->getStaticPropertyValue('qux'));

        $classInfo->setStaticPropertyValue('qux', 'quxqux');

        self::assertSame('quxqux', $classInfo->getStaticPropertyValue('qux'));
    }

    public function testSetStaticPropertyValueThrowsExceptionWhenPropertyDoesNotExist(): void
    {
        $staticPropertyGetSetFixtureFile = __DIR__ . '/../Fixture/StaticPropertyGetSet.php';
        require_once $staticPropertyGetSetFixtureFile;

        $classInfo = (new DefaultReflector(new SingleFileSourceLocator($staticPropertyGetSetFixtureFile, $this->astLocator)))
            ->reflectClass(StaticPropertyGetSet::class);

        $this->expectException(PropertyDoesNotExist::class);
        $classInfo->setStaticPropertyValue('foo', null);
    }

    public function testGetAst(): void
    {
        $php = '<?php
            class Foo {}
        ';

        $reflection = (new DefaultReflector(new StringSourceLocator($php, $this->astLocator)))->reflectClass('Foo');

        $ast = $reflection->getAst();

        self::assertInstanceOf(Class_::class, $ast);
        self::assertSame('Foo', $ast->name->name);
    }

    public function testGetConstantsReturnsAllConstantsRegardlessOfVisibility(): void
    {
        $php = '<?php
            class Foo {
                private const BAR_PRIVATE = 1;
                protected const BAR_PROTECTED = 2;
                public const BAR_PUBLIC = 3;
                const BAR_DEFAULT = 4;
            }
        ';

        $reflection = (new DefaultReflector(new StringSourceLocator($php, $this->astLocator)))->reflectClass('Foo');

        $expectedConstants = [
            'BAR_PRIVATE' => 1,
            'BAR_PROTECTED' => 2,
            'BAR_PUBLIC' => 3,
            'BAR_DEFAULT' => 4,
        ];

        self::assertSame($expectedConstants, $reflection->getConstants());

        array_walk(
            $expectedConstants,
            static function ($constantValue, string $constantName) use ($reflection): void {
                self::assertTrue($reflection->hasConstant($constantName), 'Constant ' . $constantName . ' not set');
                self::assertSame(
                    $constantValue,
                    $reflection->getConstant($constantName),
                    'Constant value for ' . $constantName . ' does not match',
                );
            },
        );
    }

    public function testGetConstantsReturnsInheritedConstants(): void
    {
        $classInfo = (new DefaultReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/InheritedClassConstants.php',
            $this->astLocator,
        )))->reflectClass('Next');

        $expectedConstants = [
            'F' => 'ff',
            'D' => 'dd',
            'C' => 'c',
            'A' => 'a',
            'B' => 'b',
        ];

        self::assertSame($expectedConstants, $classInfo->getConstants());
    }

    public function testGetImmediateConstants(): void
    {
        $classInfo = (new DefaultReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/InheritedClassConstants.php',
            $this->astLocator,
        )))->reflectClass('Next');

        self::assertSame(['F' => 'ff'], $classInfo->getImmediateConstants());
    }

    public function testGetReflectionConstantsReturnsInheritedConstants(): void
    {
        $classInfo = (new DefaultReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/InheritedClassConstants.php',
            $this->astLocator,
        )))->reflectClass('Next');

        $expectedConstants = [
            'F' => 'ff',
            'D' => 'dd',
            'C' => 'c',
            'A' => 'a',
            'B' => 'b',
        ];

        $reflectionConstants = $classInfo->getReflectionConstants();

        self::assertCount(5, $reflectionConstants);
        self::assertContainsOnlyInstancesOf(ReflectionClassConstant::class, $reflectionConstants);
        self::assertSame(array_keys($expectedConstants), array_keys($reflectionConstants));

        array_walk(
            $expectedConstants,
            static function ($constantValue, string $constantName) use ($reflectionConstants): void {
                self::assertArrayHasKey($constantName, $reflectionConstants, 'Constant ' . $constantName . ' not set');
                self::assertSame(
                    $constantValue,
                    $reflectionConstants[$constantName]->getValue(),
                    'Constant value for ' . $constantName . ' does not match',
                );
            },
        );
    }

    public function testGetImmediateReflectionConstants(): void
    {
        $classInfo = (new DefaultReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/InheritedClassConstants.php',
            $this->astLocator,
        )))->reflectClass('Next');

        $reflectionConstants = $classInfo->getImmediateReflectionConstants();

        self::assertCount(1, $reflectionConstants);
        self::assertArrayHasKey('F', $reflectionConstants);
        self::assertInstanceOf(ReflectionClassConstant::class, $reflectionConstants['F']);
        self::assertSame('ff', $reflectionConstants['F']->getValue());
    }

    public function testGetConstantsDeclaredWithOneKeyword(): void
    {
        $php = <<<'PHP'
<?php
class Foo
{
    const A = 0,
          B = 1;
}
PHP;

        $expectedConstants = [
            'A' => 0,
            'B' => 1,
        ];

        $classInfo = (new DefaultReflector(new StringSourceLocator($php, $this->astLocator)))->reflectClass('Foo');

        $constants = $classInfo->getConstants();

        self::assertCount(2, $constants);
        self::assertSame($expectedConstants, $constants);
    }

    public function testTraitRenamingMethodWithWrongCaseShouldStillWork(): void
    {
        $php = <<<'PHP'
            <?php

            trait MyTrait
            {
                protected function myMethod() : void{

                }
            }

            class HelloWorld
            {
                use MyTrait {
                    MyMethod as myRenamedMethod;
                }

                public function sayHello(int $date): void
                {
                    $this->myRenamedMethod();
                }
            }
        PHP;

        $reflection = (new DefaultReflector(new StringSourceLocator($php, $this->astLocator)))->reflectClass('HelloWorld');
        self::assertTrue($reflection->hasMethod('myRenamedMethod'));
    }

    public function testTraitSeparateUsesWithMethodRename(): void
    {
        $php = <<<'PHP'
            <?php

            trait HelloWorldTraitTest
            {
            }

            trait HelloWorldTrait
            {
               public function sayHello(): void
               {

               }
            }

            class HelloWorld
            {
               use HelloWorldTraitTest;
               use HelloWorldTrait {
                   sayHello as hello;
               }
            }
        PHP;

        $reflection = (new DefaultReflector(new StringSourceLocator($php, $this->astLocator)))->reflectClass('HelloWorld');
        self::assertTrue($reflection->hasMethod('hello'));
    }

    public function testTraitMultipleUsesWithMethodRename(): void
    {
        $php = <<<'PHP'
            <?php

            trait HelloWorldTraitTest
            {
            }

            trait HelloWorldTrait
            {
               public function sayHello(): void
               {

               }
            }

            class HelloWorld
            {
               use HelloWorldTraitTest, HelloWorldTrait {
                   sayHello as hello;
               }
            }
        PHP;

        $reflection = (new DefaultReflector(new StringSourceLocator($php, $this->astLocator)))->reflectClass('HelloWorld');
        self::assertTrue($reflection->hasMethod('hello'));
    }

    public function testTraitMethodWithModifiedVisibility(): void
    {
        $php = <<<'PHP'
            <?php

            trait BarTrait {
                private function privateMethod() {}
                protected function protectedMethod() {}
            }

            class Foo
            {
                use BarTrait {
                    protectedMethod as public;
                    privateMethod as protected privateMethodRenamed;
                }
            }
        PHP;

        $reflector       = new DefaultReflector(new StringSourceLocator($php, $this->astLocator));
        $classReflection = $reflector->reflectClass('Foo');
        $traitReflection = $classReflection->getTraits()[0];

        $protectedMethodFromClass = $classReflection->getMethod('protectedMethod');
        self::assertTrue($protectedMethodFromClass->isPublic());
        self::assertFalse($protectedMethodFromClass->isProtected());

        $protectedMethodFromTrait = $traitReflection->getMethod('protectedMethod');
        self::assertFalse($protectedMethodFromTrait->isPublic());
        self::assertTrue($protectedMethodFromTrait->isProtected());

        self::assertNotSame($protectedMethodFromClass->getAst(), $protectedMethodFromTrait->getAst());

        $privateMethodFromClass = $classReflection->getMethod('privateMethod');
        self::assertTrue($privateMethodFromClass->isProtected());
        self::assertFalse($privateMethodFromClass->isPrivate());

        $privateMethodFromTrait = $traitReflection->getMethod('privateMethod');
        self::assertFalse($privateMethodFromTrait->isProtected());
        self::assertTrue($privateMethodFromTrait->isPrivate());

        self::assertNotSame($privateMethodFromClass->getAst(), $privateMethodFromTrait->getAst());

        $privateMethodRenamed = $classReflection->getMethod('privateMethodRenamed');
        self::assertTrue($privateMethodRenamed->isProtected());
        self::assertFalse($privateMethodRenamed->isPrivate());
    }

    public function testChildClassHasRenamedTraitMethodFromParent(): void
    {
        $php = <<<'PHP'
            <?php

            trait SomeTrait
            {
                public function someNumber()
                {
                }
            }

            class ParentClass {
                use SomeTrait {
                    someNumber as myNumber;
                }
            }

            class SubClass extends ParentClass
            {
            }
        PHP;

        $reflector = new DefaultReflector(new StringSourceLocator($php, $this->astLocator));

        $parentClass = $reflector->reflectClass('ParentClass');

        self::assertTrue($parentClass->hasMethod('someNumber'));
        self::assertTrue($parentClass->hasMethod('myNumber'));

        $subClass = $reflector->reflectClass('SubClass');

        self::assertTrue($subClass->hasMethod('someNumber'));
        self::assertTrue($subClass->hasMethod('myNumber'));
    }

    public function testHasStringableInterface(): void
    {
        $php = <<<'PHP'
            <?php

            class ClassHasStringable implements Stringable
            {
                public function __toString(): string
                {
                }
            }

            class ClassHasStringableAutomatically
            {
                public function __toString(): string
                {
                }
            }

            interface InterfaceHasStringable extends \Stringable
            {
            }

            interface InterfaceHasStringableAutomatically
            {
                public function __toString();
            }
        PHP;

        $reflector = new DefaultReflector(new AggregateSourceLocator([
            new StringSourceLocator($php, $this->astLocator),
            BetterReflectionSingleton::instance()->sourceLocator(),
        ]));

        $classImplementingStringable = $reflector->reflectClass('ClassHasStringable');
        self::assertContains(Stringable::class, $classImplementingStringable->getInterfaceNames());
        self::assertArrayHasKey(Stringable::class, $classImplementingStringable->getImmediateInterfaces());

        $classNotImplementingStringable = $reflector->reflectClass('ClassHasStringableAutomatically');
        self::assertContains(Stringable::class, $classNotImplementingStringable->getInterfaceNames());
        self::assertArrayHasKey(Stringable::class, $classNotImplementingStringable->getImmediateInterfaces());

        $interfaceExtendingStringable = $reflector->reflectClass('InterfaceHasStringable');
        self::assertContains(Stringable::class, $interfaceExtendingStringable->getInterfaceNames());
        self::assertArrayHasKey(Stringable::class, $interfaceExtendingStringable->getImmediateInterfaces());

        $interfaceNotExtendingStringable = $reflector->reflectClass('InterfaceHasStringableAutomatically');
        self::assertContains(Stringable::class, $interfaceNotExtendingStringable->getInterfaceNames());
        self::assertArrayHasKey(Stringable::class, $interfaceNotExtendingStringable->getImmediateInterfaces());
    }

    public function testNoStringableInterfaceWhenStringableNotFound(): void
    {
        $php = <<<'PHP'
            <?php

            class NoStringable
            {
                public function __toString(): string
                {
                }
            }
        PHP;

        $reflector = new DefaultReflector(new StringSourceLocator($php, $this->astLocator));

        $noStringable = $reflector->reflectClass('NoStringable');

        self::assertNotContains(Stringable::class, $noStringable->getInterfaceNames());
    }

    public function testNoStringableInterfaceWhenStringableIsNotInternal(): void
    {
        $php = <<<'PHP'
            <?php

            class Stringable
            {
            }

            class NoStringable
            {
                public function __toString(): string
                {
                }
            }
        PHP;

        $reflector = new DefaultReflector(new StringSourceLocator($php, $this->astLocator));

        $noStringable = $reflector->reflectClass('NoStringable');

        self::assertNotContains(Stringable::class, $noStringable->getInterfaceNames());
    }

    public function testHasAllInterfacesWithStringable(): void
    {
        $php = <<<'PHP'
            <?php

            abstract class HasStringable implements Iterator
            {
                public function __toString(): string
                {
                }
            }
        PHP;

        $reflector = new DefaultReflector(new AggregateSourceLocator([
            new StringSourceLocator($php, $this->astLocator),
            BetterReflectionSingleton::instance()->sourceLocator(),
        ]));

        $class = $reflector->reflectClass('HasStringable');

        self::assertSame(['Iterator', 'Traversable', 'Stringable'], $class->getInterfaceNames());
    }

    public function deprecatedDocCommentProvider(): array
    {
        return [
            [
                '/**
                  * @deprecated since 8.0
                  */',
                true,
            ],
            [
                '/**
                  * @deprecated
                  */',
                true,
            ],
            [
                '',
                false,
            ],
        ];
    }

    /**
     * @dataProvider deprecatedDocCommentProvider
     */
    public function testIsDeprecated(string $docComment, bool $isDeprecated): void
    {
        $php = sprintf('<?php
        %s
        class Foo {}', $docComment);

        $reflector       = new DefaultReflector(new StringSourceLocator($php, $this->astLocator));
        $classReflection = $reflector->reflectClass('Foo');

        self::assertSame($isDeprecated, $classReflection->isDeprecated());
    }

    public function testIsEnum(): void
    {
        $php = <<<'PHP'
            <?php

            enum IsEnum
            {
                case Bar;
            }

            class IsNotEnum
            {
            }
        PHP;

        $reflector = new DefaultReflector(new StringSourceLocator($php, $this->astLocator));

        $isEnum = $reflector->reflectClass('IsEnum');
        self::assertTrue($isEnum->isEnum());

        $isNotEnum = $reflector->reflectClass('IsNotEnum');
        self::assertFalse($isNotEnum->isEnum());
    }

    public function testGetAttributesWithoutAttributes(): void
    {
        $reflector       = new DefaultReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/ExampleClass.php', $this->astLocator));
        $classReflection = $reflector->reflectClass(ExampleClass::class);
        $attributes      = $classReflection->getAttributes();

        self::assertCount(0, $attributes);
    }

    public function testGetAttributesWithAttributes(): void
    {
        $reflector       = new DefaultReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/Attributes.php', $this->astLocator));
        $classReflection = $reflector->reflectClass(ClassWithAttributes::class);
        $attributes      = $classReflection->getAttributes();

        self::assertCount(2, $attributes);
    }

    public function testGetAttributesByName(): void
    {
        $reflector       = new DefaultReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/Attributes.php', $this->astLocator));
        $classReflection = $reflector->reflectClass(ClassWithAttributes::class);
        $attributes      = $classReflection->getAttributesByName(Attr::class);

        self::assertCount(1, $attributes);
    }

    public function testGetAttributesByInstance(): void
    {
        $reflector       = new DefaultReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/Attributes.php', $this->astLocator));
        $classReflection = $reflector->reflectClass(ClassWithAttributes::class);
        $attributes      = $classReflection->getAttributesByInstance(Attr::class);

        self::assertCount(2, $attributes);
    }

    public function testBugWithTraitMethodVisibilityOverride(): void
    {
        $php            = <<<'PHP'
            <?php

            interface Foo
            {
                public function doFoo(): void;
            }

            class Bar implements Foo
            {
                public function doFoo(): void
                {
                }
            }

            trait SpecificFoo
            {
                public function doFoo(): void
                {
                }
            }

            class Baz extends Bar
            {
                use SpecificFoo {
                    doFoo as private doFooImpl;
                }

                public function doFoo(): void
                {
                }
            }

            class FooBar extends Bar
            {
                use SpecificFoo;
            }
        PHP;
        $reflector      = new DefaultReflector(new MemoizingSourceLocator(new StringSourceLocator($php, $this->astLocator)));
        $baz            = $reflector->reflectClass('Baz');
        $bazDoFooMethod = $baz->getMethod('doFoo');
        self::assertTrue($bazDoFooMethod->isPublic());

        $bazDoFooImplMethod = $baz->getMethod('doFooImpl');
        self::assertFalse($bazDoFooImplMethod->isPublic());

        $fooBar            = $reflector->reflectClass('FooBar');
        $fooBarDoFooMethod = $fooBar->getMethod('doFoo');
        self::assertTrue($fooBarDoFooMethod->isPublic());
    }
}
