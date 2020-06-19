<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection;

use Bar;
use Baz;
use E;
use InvalidArgumentException;
use Iterator;
use OutOfBoundsException;
use Php4StyleCaseInsensitiveConstruct;
use Php4StyleConstruct;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Parser;
use PHPUnit\Framework\TestCase;
use Qux;
use Reflection as CoreReflection;
use ReflectionClass as CoreReflectionClass;
use ReflectionMethod as CoreReflectionMethod;
use ReflectionProperty as CoreReflectionProperty;
use Roave\BetterReflection\Reflection\Exception\NotAClassReflection;
use Roave\BetterReflection\Reflection\Exception\NotAnInterfaceReflection;
use Roave\BetterReflection\Reflection\Exception\NotAnObject;
use Roave\BetterReflection\Reflection\Exception\PropertyDoesNotExist;
use Roave\BetterReflection\Reflection\Exception\Uncloneable;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionClassConstant;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use Roave\BetterReflection\Reflection\ReflectionProperty;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\Type\AnonymousClassObjectSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\ComposerSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;
use Roave\BetterReflectionTest\BetterReflectionSingleton;
use Roave\BetterReflectionTest\ClassesImplementingIterators;
use Roave\BetterReflectionTest\ClassesWithCloneMethod;
use Roave\BetterReflectionTest\ClassesWithPublicOrNonPublicContructor\ClassWithExtendedConstructor;
use Roave\BetterReflectionTest\ClassesWithPublicOrNonPublicContructor\ClassWithoutConstructor;
use Roave\BetterReflectionTest\ClassesWithPublicOrNonPublicContructor\ClassWithPrivateConstructor;
use Roave\BetterReflectionTest\ClassesWithPublicOrNonPublicContructor\ClassWithProtectedConstructor;
use Roave\BetterReflectionTest\ClassesWithPublicOrNonPublicContructor\ClassWithPublicConstructor;
use Roave\BetterReflectionTest\ClassWithInterfaces;
use Roave\BetterReflectionTest\ClassWithInterfacesExtendingInterfaces;
use Roave\BetterReflectionTest\ClassWithInterfacesOther;
use Roave\BetterReflectionTest\Fixture;
use Roave\BetterReflectionTest\Fixture\AbstractClass;
use Roave\BetterReflectionTest\Fixture\ClassForHinting;
use Roave\BetterReflectionTest\Fixture\ExampleClass;
use Roave\BetterReflectionTest\Fixture\ExampleInterface;
use Roave\BetterReflectionTest\Fixture\ExampleTrait;
use Roave\BetterReflectionTest\Fixture\FinalClass;
use Roave\BetterReflectionTest\Fixture\InvalidInheritances;
use Roave\BetterReflectionTest\Fixture\MethodsOrder;
use Roave\BetterReflectionTest\Fixture\StaticProperties;
use Roave\BetterReflectionTest\Fixture\StaticPropertyGetSet;
use Roave\BetterReflectionTest\Fixture\UpperCaseConstructDestruct;
use Roave\BetterReflectionTest\FixtureOther\AnotherClass;
use stdClass;
use function array_keys;
use function array_map;
use function array_walk;
use function basename;
use function class_exists;
use function count;
use function file_get_contents;
use function sort;
use function uniqid;

/**
 * @covers \Roave\BetterReflection\Reflection\ReflectionClass
 */
class ReflectionClassTest extends TestCase
{
    /** @var Locator */
    private $astLocator;

    /** @var Parser */
    private $parser;

    protected function setUp() : void
    {
        parent::setUp();

        $this->astLocator = BetterReflectionSingleton::instance()->astLocator();
        $this->parser = BetterReflectionSingleton::instance()->phpParser();
    }

    private function getComposerLocator() : ComposerSourceLocator
    {
        return new ComposerSourceLocator($GLOBALS['loader'], $this->astLocator);
    }

    public function testCanReflectInternalClassWithDefaultLocator() : void
    {
        self::assertSame(stdClass::class, ReflectionClass::createFromName(stdClass::class)->getName());
    }

    public function testCanReflectInstance() : void
    {
        $instance = new stdClass();
        self::assertSame(stdClass::class, ReflectionClass::createFromInstance($instance)->getName());
    }

    public function testCreateFromInstanceThrowsExceptionWhenInvalidArgumentProvided() : void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Instance must be an instance of an object');
        ReflectionClass::createFromInstance('invalid argument');
    }

    public function testCanReflectEvaledClassWithDefaultLocator() : void
    {
        $className = uniqid('foo', false);

        eval('class ' . $className . '{}');

        self::assertSame($className, ReflectionClass::createFromName($className)->getName());
    }

    public function testClassNameMethodsWithNamespace() : void
    {
        $reflector = new ClassReflector($this->getComposerLocator());
        $classInfo = $reflector->reflect(ExampleClass::class);

        self::assertTrue($classInfo->inNamespace());
        self::assertSame(ExampleClass::class, $classInfo->getName());
        self::assertSame('Roave\BetterReflectionTest\Fixture', $classInfo->getNamespaceName());
        self::assertSame('ExampleClass', $classInfo->getShortName());
    }

    public function testClassNameMethodsWithoutNamespace() : void
    {
        $classInfo = (new ClassReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/NoNamespace.php',
            $this->astLocator
        )))->reflect('ClassWithNoNamespace');

        self::assertFalse($classInfo->inNamespace());
        self::assertSame('ClassWithNoNamespace', $classInfo->getName());
        self::assertSame('', $classInfo->getNamespaceName());
        self::assertSame('ClassWithNoNamespace', $classInfo->getShortName());
    }

    public function testClassNameMethodsWithExplicitGlobalNamespace() : void
    {
        $classInfo = (new ClassReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/ExampleClass.php',
            $this->astLocator
        )))->reflect('ClassWithExplicitGlobalNamespace');

        self::assertFalse($classInfo->inNamespace());
        self::assertSame('ClassWithExplicitGlobalNamespace', $classInfo->getName());
        self::assertSame('', $classInfo->getNamespaceName());
        self::assertSame('ClassWithExplicitGlobalNamespace', $classInfo->getShortName());
    }

    /**
     * @coversNothing
     */
    public function testReflectingAClassDoesNotLoadTheClass() : void
    {
        self::assertFalse(class_exists(ExampleClass::class, false));

        $reflector = new ClassReflector($this->getComposerLocator());
        $reflector->reflect(ExampleClass::class);

        self::assertFalse(class_exists(ExampleClass::class, false));
    }

    public function testGetMethods() : void
    {
        $reflector = new ClassReflector($this->getComposerLocator());
        $classInfo = $reflector->reflect(ExampleClass::class);
        self::assertGreaterThanOrEqual(1, $classInfo->getMethods());
    }

    public function getMethodsWithFilterDataProvider() : array
    {
        return [
            [CoreReflectionMethod::IS_STATIC, 1],
            [CoreReflectionMethod::IS_ABSTRACT, 1],
            [CoreReflectionMethod::IS_FINAL, 1],
            [CoreReflectionMethod::IS_PUBLIC, 16],
            [CoreReflectionMethod::IS_PROTECTED, 1],
            [CoreReflectionMethod::IS_PRIVATE, 1],
            [
                CoreReflectionMethod::IS_STATIC |
                CoreReflectionMethod::IS_ABSTRACT |
                CoreReflectionMethod::IS_FINAL |
                CoreReflectionMethod::IS_PUBLIC |
                CoreReflectionMethod::IS_PROTECTED |
                CoreReflectionMethod::IS_PRIVATE,
                18,
            ],
        ];
    }

    /**
     * @dataProvider getMethodsWithFilterDataProvider
     */
    public function testGetMethodsWithFilter(int $filter, int $count) : void
    {
        $reflector = new ClassReflector($this->getComposerLocator());
        $classInfo = $reflector->reflect(Fixture\Methods::class);

        self::assertCount($count, $classInfo->getMethods($filter));
        self::assertCount($count, $classInfo->getImmediateMethods($filter));
    }

    public function testGetMethodsReturnsInheritedMethods() : void
    {
        $classInfo = (new ClassReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/InheritedClassMethods.php',
            $this->astLocator
        )))->reflect('Qux');

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

    public function testGetMethodsOrder() : void
    {
        $classInfo = (new ClassReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/MethodsOrder.php',
            $this->astLocator
        )))->reflect(MethodsOrder::class);

        $actualMethodNames = array_map(static function (ReflectionMethod $method) : string {
            return $method->getName();
        }, $classInfo->getMethods());

        $expectedMethodNames = [
            'first',
            'second',
            'third',
            'forth',
        ];

        self::assertSame($expectedMethodNames, $actualMethodNames);
    }

    public function testGetImmediateMethods() : void
    {
        $classInfo = (new ClassReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/InheritedClassMethods.php',
            $this->astLocator
        )))->reflect('Qux');

        $methods = $classInfo->getImmediateMethods();

        self::assertCount(1, $methods);
        self::assertInstanceOf(ReflectionMethod::class, $methods['f']);
        self::assertSame('f', $methods['f']->getName());
    }

    public function testGetConstants() : void
    {
        $reflector = new ClassReflector($this->getComposerLocator());
        $classInfo = $reflector->reflect(ExampleClass::class);
        self::assertSame([
            'MY_CONST_1' => 123,
            'MY_CONST_2' => 234,
            'MY_CONST_3' => 345,
            'MY_CONST_4' => 456,
            'MY_CONST_5' => 567,
        ], $classInfo->getConstants());
    }

    public function testGetConstant() : void
    {
        $reflector = new ClassReflector($this->getComposerLocator());
        $classInfo = $reflector->reflect(ExampleClass::class);
        self::assertSame(123, $classInfo->getConstant('MY_CONST_1'));
        self::assertSame(234, $classInfo->getConstant('MY_CONST_2'));
        self::assertSame(345, $classInfo->getConstant('MY_CONST_3'));
        self::assertSame(456, $classInfo->getConstant('MY_CONST_4'));
        self::assertSame(567, $classInfo->getConstant('MY_CONST_5'));
        self::assertNull($classInfo->getConstant('NON_EXISTENT_CONSTANT'));
    }

    public function testGetReflectionConstants() : void
    {
        $reflector = new ClassReflector($this->getComposerLocator());
        $classInfo = $reflector->reflect(ExampleClass::class);
        self::assertCount(5, $classInfo->getReflectionConstants());
    }

    public function testGetReflectionConstant() : void
    {
        $reflector = new ClassReflector($this->getComposerLocator());
        $classInfo = $reflector->reflect(ExampleClass::class);
        self::assertSame(123, $classInfo->getReflectionConstant('MY_CONST_1')->getValue());
        self::assertSame(234, $classInfo->getReflectionConstant('MY_CONST_2')->getValue());
        self::assertSame(345, $classInfo->getReflectionConstant('MY_CONST_3')->getValue());
        self::assertSame(456, $classInfo->getReflectionConstant('MY_CONST_4')->getValue());
        self::assertSame(567, $classInfo->getReflectionConstant('MY_CONST_5')->getValue());
        self::assertNull($classInfo->getConstant('NON_EXISTENT_CONSTANT'));
    }

    public function testGetConstructor() : void
    {
        $reflector   = new ClassReflector($this->getComposerLocator());
        $classInfo   = $reflector->reflect(ExampleClass::class);
        $constructor = $classInfo->getConstructor();

        self::assertInstanceOf(ReflectionMethod::class, $constructor);
        self::assertTrue($constructor->isConstructor());
    }

    public function testGetCaseInsensitiveConstructor() : void
    {
        $reflector   = new ClassReflector($this->getComposerLocator());
        $classInfo   = $reflector->reflect(UpperCaseConstructDestruct::class);
        $constructor = $classInfo->getConstructor();

        self::assertInstanceOf(ReflectionMethod::class, $constructor);
        self::assertTrue($constructor->isConstructor());
    }

    public function testGetConstructorWhenPhp4Style() : void
    {
        $classInfo = (new ClassReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/Php4StyleConstruct.php',
            $this->astLocator
        )))->reflect(Php4StyleConstruct::class);

        $constructor = $classInfo->getConstructor();

        self::assertInstanceOf(ReflectionMethod::class, $constructor);
        self::assertTrue($constructor->isConstructor());
    }

    public function testGetConstructorWhenPhp4StyleInNamespace() : void
    {
        $this->expectException(OutOfBoundsException::class);

        $classInfo = (new ClassReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/Php4StyleConstructInNamespace.php',
            $this->astLocator
        )))->reflect(Fixture\Php4StyleConstructInNamespace::class);

        $classInfo->getConstructor();
    }

    public function testGetConstructorWhenPhp4StyleCaseInsensitive() : void
    {
        $classInfo = (new ClassReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/Php4StyleCaseInsensitiveConstruct.php',
            $this->astLocator
        )))->reflect(Php4StyleCaseInsensitiveConstruct::class);

        $constructor = $classInfo->getConstructor();

        self::assertInstanceOf(ReflectionMethod::class, $constructor);
        self::assertTrue($constructor->isConstructor());
    }

    public function testGetProperties() : void
    {
        $reflector = new ClassReflector($this->getComposerLocator());
        $classInfo = $reflector->reflect(ExampleClass::class);

        $properties = $classInfo->getProperties();

        self::assertContainsOnlyInstancesOf(ReflectionProperty::class, $properties);
        self::assertCount(4, $properties);
    }

    public function testGetPropertiesDeclaredWithOneKeyword() : void
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

        $classInfo = (new ClassReflector(new StringSourceLocator($php, $this->astLocator)))->reflect('Foo');

        $properties = $classInfo->getProperties();

        self::assertCount(6, $properties);
        self::assertSame($expectedPropertiesNames, array_keys($properties));
    }

    public function getPropertiesWithFilterDataProvider() : array
    {
        return [
            [CoreReflectionProperty::IS_STATIC, 1],
            [CoreReflectionProperty::IS_PUBLIC, 2],
            [CoreReflectionProperty::IS_PROTECTED, 1],
            [CoreReflectionProperty::IS_PRIVATE, 1],
            [
                CoreReflectionProperty::IS_STATIC |
                CoreReflectionProperty::IS_PUBLIC |
                CoreReflectionProperty::IS_PROTECTED |
                CoreReflectionProperty::IS_PRIVATE,
                4,
            ],
        ];
    }

    /**
     * @dataProvider getPropertiesWithFilterDataProvider
     */
    public function testGetPropertiesWithFilter(int $filter, int $count) : void
    {
        $reflector = new ClassReflector($this->getComposerLocator());
        $classInfo = $reflector->reflect(ExampleClass::class);

        self::assertCount($count, $classInfo->getProperties($filter));
        self::assertCount($count, $classInfo->getImmediateProperties($filter));
    }

    public function testGetPropertiesReturnsInheritedProperties() : void
    {
        $classInfo = (new ClassReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/InheritedClassProperties.php',
            $this->astLocator
        )))->reflect(Qux::class);

        $properties = $classInfo->getProperties();
        self::assertCount(5, $properties);
        self::assertContainsOnlyInstancesOf(ReflectionProperty::class, $properties);

        self::assertSame('a', $classInfo->getProperty('a')->getName(), 'Failed asserting that property a from trait Bar was returned');
        self::assertSame(Bar::class, $classInfo->getProperty('a')->getDeclaringClass()->getName());

        self::assertSame('b', $classInfo->getProperty('b')->getName(), 'Failed asserting that private property b from trait Bar was returned');
        self::assertSame(Bar::class, $classInfo->getProperty('b')->getDeclaringClass()->getName());

        self::assertSame('c', $classInfo->getProperty('c')->getName(), 'Failed asserting that public property c from parent class Baz was returned');
        self::assertSame(Baz::class, $classInfo->getProperty('c')->getDeclaringClass()->getName());

        self::assertSame('d', $classInfo->getProperty('d')->getName(), 'Failed asserting that protected property d from parent class Baz was returned');
        self::assertSame(Baz::class, $classInfo->getProperty('d')->getDeclaringClass()->getName());

        self::assertSame('f', $classInfo->getProperty('f')->getName(), 'Failed asserting that property f from SUT was returned');
        self::assertSame(Qux::class, $classInfo->getProperty('f')->getDeclaringClass()->getName());
    }

    public function testGetImmediateProperties() : void
    {
        $classInfo = (new ClassReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/InheritedClassProperties.php',
            $this->astLocator
        )))->reflect(Qux::class);

        $properties = $classInfo->getImmediateProperties();
        self::assertCount(1, $properties);
        self::assertContainsOnlyInstancesOf(ReflectionProperty::class, $properties);

        self::assertSame('f', $classInfo->getProperty('f')->getName(), 'Failed asserting that property f from SUT was returned');
        self::assertSame(Qux::class, $classInfo->getProperty('f')->getDeclaringClass()->getName());
    }

    public function testGetProperty() : void
    {
        $reflector = new ClassReflector($this->getComposerLocator());
        $classInfo = $reflector->reflect(ExampleClass::class);

        self::assertNull($classInfo->getProperty('aNonExistentProperty'));

        $property = $classInfo->getProperty('publicProperty');

        self::assertInstanceOf(ReflectionProperty::class, $property);
        self::assertSame('publicProperty', $property->getName());
        self::assertStringEndsWith('test/unit/Fixture', $property->getDefaultValue());
    }

    public function testGetFileName() : void
    {
        $reflector = new ClassReflector($this->getComposerLocator());
        $classInfo = $reflector->reflect(ExampleClass::class);

        $detectedFilename = $classInfo->getFileName();

        self::assertSame('ExampleClass.php', basename($detectedFilename));
    }

    public function testStaticCreation() : void
    {
        $reflection = ReflectionClass::createFromName(ExampleClass::class);
        self::assertSame('ExampleClass', $reflection->getShortName());
    }

    public function testGetParentClassDefault() : void
    {
        $childReflection = (new ClassReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/ExampleClass.php',
            $this->astLocator
        )))->reflect(Fixture\ClassWithParent::class);

        $parentReflection = $childReflection->getParentClass();
        self::assertSame('ExampleClass', $parentReflection->getShortName());
    }

    public function testGetParentClassThrowsExceptionWithNoParent() : void
    {
        $reflection = ReflectionClass::createFromName(ExampleClass::class);

        self::assertNull($reflection->getParentClass());
    }

    public function testGetParentClassNames() : void
    {
        $childReflection = (new ClassReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/ExampleClass.php',
            $this->astLocator
        )))->reflect(Fixture\ClassWithTwoParents::class);

        self::assertSame(['Roave\\BetterReflectionTest\\Fixture\\ClassWithParent', 'Roave\\BetterReflectionTest\\Fixture\\ExampleClass'], $childReflection->getParentClassNames());
    }

    public function startEndLineProvider() : array
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
    public function testStartEndLine(string $php, int $expectedStart, int $expectedEnd) : void
    {
        $reflector = new ClassReflector(new StringSourceLocator($php, $this->astLocator));
        $classInfo = $reflector->reflect('Foo');

        self::assertSame($expectedStart, $classInfo->getStartLine());
        self::assertSame($expectedEnd, $classInfo->getEndLine());
    }

    public function columnsProvider() : array
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
    public function testGetStartColumnAndEndColumn(string $php, int $startColumn, int $endColumn) : void
    {
        $reflector = new ClassReflector(new StringSourceLocator($php, $this->astLocator));
        $classInfo = $reflector->reflect('Foo');

        self::assertSame($startColumn, $classInfo->getStartColumn());
        self::assertSame($endColumn, $classInfo->getEndColumn());
    }

    public function testGetDocComment() : void
    {
        $reflector = new ClassReflector($this->getComposerLocator());
        $classInfo = $reflector->reflect(ExampleClass::class);

        self::assertStringContainsString('Some comments here', $classInfo->getDocComment());
    }

    public function testGetDocCommentBetweeenComments() : void
    {
        $php       = '<?php
            /* A comment */
            /** Class description */
            /* An another comment */
            class Bar implements Foo {}
        ';
        $reflector = (new ClassReflector(new StringSourceLocator($php, $this->astLocator)))->reflect('Bar');

        self::assertStringContainsString('Class description', $reflector->getDocComment());
    }

    public function testGetDocCommentReturnsEmptyStringWithNoComment() : void
    {
        $classInfo = (new ClassReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/ExampleClass.php',
            $this->astLocator
        )))->reflect(AnotherClass::class);

        self::assertSame('', $classInfo->getDocComment());
    }

    public function testHasProperty() : void
    {
        $reflector = new ClassReflector($this->getComposerLocator());
        $classInfo = $reflector->reflect(ExampleClass::class);

        self::assertFalse($classInfo->hasProperty('aNonExistentProperty'));
        self::assertTrue($classInfo->hasProperty('publicProperty'));
    }

    public function testHasConstant() : void
    {
        $reflector = new ClassReflector($this->getComposerLocator());
        $classInfo = $reflector->reflect(ExampleClass::class);

        self::assertFalse($classInfo->hasConstant('NON_EXISTENT_CONSTANT'));
        self::assertTrue($classInfo->hasConstant('MY_CONST_1'));
    }

    public function testHasMethod() : void
    {
        $reflector = new ClassReflector($this->getComposerLocator());
        $classInfo = $reflector->reflect(ExampleClass::class);

        self::assertFalse($classInfo->hasMethod('aNonExistentMethod'));
        self::assertTrue($classInfo->hasMethod('someMethod'));
    }

    public function testGetDefaultProperties() : void
    {
        $classInfo = (new ClassReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/DefaultProperties.php',
            $this->astLocator
        )))->reflect('Foo');

        self::assertSame([
            'hasDefault' => 123,
            'noDefault' => null,
        ], $classInfo->getDefaultProperties());
    }

    public function testIsAnonymousWithNotAnonymousClass() : void
    {
        $classInfo = (new ClassReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/ExampleClass.php',
            $this->astLocator
        )))->reflect(ExampleClass::class);

        self::assertFalse($classInfo->isAnonymous());
    }

    public function testIsAnonymousWithAnonymousClassNoNamespace() : void
    {
        $reflector = new ClassReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/AnonymousClassNoNamespace.php',
            $this->astLocator
        ));

        $allClassesInfo = $reflector->getAllClasses();
        self::assertCount(1, $allClassesInfo);

        $classInfo = $allClassesInfo[0];
        self::assertTrue($classInfo->isAnonymous());
        self::assertFalse($classInfo->inNamespace());
        self::assertStringStartsWith(ReflectionClass::ANONYMOUS_CLASS_NAME_PREFIX, $classInfo->getName());
        self::assertStringEndsWith('Fixture/AnonymousClassNoNamespace.php(3)', $classInfo->getName());
    }

    public function testIsAnonymousWithParentClass() : void
    {
        $reflector = new ClassReflector(
            new StringSourceLocator('<?php new class extends ClassForHinting {};', $this->astLocator)
        );
        $parent = $reflector->getAllClasses()[0]->getParentClass();
        self::assertSame(ClassForHinting::class, $parent->getName());

        $reflector = new ClassReflector(
            new AnonymousClassObjectSourceLocator(
                new class extends ClassForHinting {
                },
                $this->parser
            )
        );
        $parent = $reflector->getAllClasses()[0]->getParentClass();
        self::assertSame(ClassForHinting::class, $parent->getName());
    }

    public function testIsAnonymousWithAnonymousClassInNamespace() : void
    {
        $reflector = new ClassReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/AnonymousClassInNamespace.php',
            $this->astLocator
        ));

        $allClassesInfo = $reflector->getAllClasses();
        self::assertCount(2, $allClassesInfo);

        foreach ($allClassesInfo as $classInfo) {
            self::assertTrue($classInfo->isAnonymous());
            self::assertFalse($classInfo->inNamespace());
            self::assertStringStartsWith(ReflectionClass::ANONYMOUS_CLASS_NAME_PREFIX, $classInfo->getName());
            self::assertStringMatchesFormat('%sFixture/AnonymousClassInNamespace.php(%d)', $classInfo->getName());
        }
    }

    public function testIsAnonymousWithNestedAnonymousClasses() : void
    {
        $reflector = new ClassReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/NestedAnonymousClassInstances.php',
            $this->astLocator
        ));

        $allClassesInfo = $reflector->getAllClasses();
        self::assertCount(3, $allClassesInfo);

        foreach ($allClassesInfo as $classInfo) {
            self::assertTrue($classInfo->isAnonymous());
            self::assertFalse($classInfo->inNamespace());
            self::assertStringStartsWith(ReflectionClass::ANONYMOUS_CLASS_NAME_PREFIX, $classInfo->getName());
            self::assertStringMatchesFormat('%sFixture/NestedAnonymousClassInstances.php(%d)', $classInfo->getName());
        }
    }

    public function testIsAnonymousWithAnonymousClassInString() : void
    {
        $php = '<?php
            function createAnonymous()
            {
                return new class {};
            }
        ';

        $reflector = new ClassReflector(new StringSourceLocator($php, $this->astLocator));

        $allClassesInfo = $reflector->getAllClasses();
        self::assertCount(1, $allClassesInfo);

        $classInfo = $allClassesInfo[0];
        self::assertTrue($classInfo->isAnonymous());
        self::assertFalse($classInfo->inNamespace());
        self::assertStringStartsWith(ReflectionClass::ANONYMOUS_CLASS_NAME_PREFIX, $classInfo->getName());
        self::assertStringEndsWith('(4)', $classInfo->getName());
    }

    public function testIsInternalWithUserDefinedClass() : void
    {
        $reflector = new ClassReflector($this->getComposerLocator());
        $classInfo = $reflector->reflect(ExampleClass::class);

        self::assertFalse($classInfo->isInternal());
        self::assertTrue($classInfo->isUserDefined());
        self::assertNull($classInfo->getExtensionName());
    }

    public function testIsInternalWithInternalClass() : void
    {
        $classInfo = BetterReflectionSingleton::instance()->classReflector()->reflect(stdClass::class);

        self::assertTrue($classInfo->isInternal());
        self::assertFalse($classInfo->isUserDefined());
        self::assertSame('Core', $classInfo->getExtensionName());
    }

    public function testIsAbstract() : void
    {
        $reflector = new ClassReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/ExampleClass.php',
            $this->astLocator
        ));

        $classInfo = $reflector->reflect(AbstractClass::class);
        self::assertTrue($classInfo->isAbstract());

        $classInfo = $reflector->reflect(ExampleClass::class);
        self::assertFalse($classInfo->isAbstract());
    }

    public function testIsFinal() : void
    {
        $reflector = new ClassReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/ExampleClass.php',
            $this->astLocator
        ));

        $classInfo = $reflector->reflect(FinalClass::class);
        self::assertTrue($classInfo->isFinal());

        $classInfo = $reflector->reflect(ExampleClass::class);
        self::assertFalse($classInfo->isFinal());
    }

    public function modifierProvider() : array
    {
        return [
            ['ExampleClass', 0, []],
            ['AbstractClass', CoreReflectionClass::IS_EXPLICIT_ABSTRACT, ['abstract']],
            ['FinalClass', CoreReflectionClass::IS_FINAL, ['final']],
        ];
    }

    /**
     * @param string[] $expectedModifierNames
     *
     * @dataProvider modifierProvider
     */
    public function testGetModifiers(string $className, int $expectedModifier, array $expectedModifierNames) : void
    {
        $reflector = new ClassReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/ExampleClass.php',
            $this->astLocator
        ));

        $classInfo = $reflector->reflect('\Roave\BetterReflectionTest\Fixture\\' . $className);

        self::assertSame($expectedModifier, $classInfo->getModifiers());
        self::assertSame(
            $expectedModifierNames,
            CoreReflection::getModifierNames($classInfo->getModifiers())
        );
    }

    public function testIsTrait() : void
    {
        $reflector = new ClassReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/ExampleClass.php',
            $this->astLocator
        ));

        $classInfo = $reflector->reflect(ExampleTrait::class);
        self::assertTrue($classInfo->isTrait());

        $classInfo = $reflector->reflect(ExampleClass::class);
        self::assertFalse($classInfo->isTrait());
    }

    public function testIsInterface() : void
    {
        $reflector = new ClassReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/ExampleClass.php',
            $this->astLocator
        ));

        $classInfo = $reflector->reflect(ExampleInterface::class);
        self::assertTrue($classInfo->isInterface());

        $classInfo = $reflector->reflect(ExampleClass::class);
        self::assertFalse($classInfo->isInterface());
    }

    public function testGetTraits() : void
    {
        $reflector = new ClassReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/TraitFixture.php',
            $this->astLocator
        ));

        $classInfo = $reflector->reflect('TraitFixtureA');
        $traits    = $classInfo->getTraits();

        self::assertCount(1, $traits);
        self::assertInstanceOf(ReflectionClass::class, $traits[0]);
        self::assertTrue($traits[0]->isTrait());
    }

    public function testGetTraitsReturnsEmptyArrayWhenNoTraitsUsed() : void
    {
        $reflector = new ClassReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/TraitFixture.php',
            $this->astLocator
        ));

        $classInfo = $reflector->reflect('TraitFixtureB');
        $traits    = $classInfo->getTraits();

        self::assertCount(0, $traits);
    }

    public function testGetTraitNames() : void
    {
        $reflector = new ClassReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/TraitFixture.php',
            $this->astLocator
        ));

        self::assertSame(
            ['TraitFixtureTraitA'],
            $reflector->reflect('TraitFixtureA')->getTraitNames()
        );
    }

    public function testGetTraitAliases() : void
    {
        $reflector = new ClassReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/TraitFixture.php',
            $this->astLocator
        ));

        $classInfo = $reflector->reflect('TraitFixtureC');

        self::assertSame([
            'a_protected' => 'TraitFixtureTraitC::a',
            'b_renamed' => 'TraitFixtureTraitC::b',
        ], $classInfo->getTraitAliases());
    }

    public function testMethodsFromTraits() : void
    {
        $reflector = new ClassReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/TraitFixture.php',
            $this->astLocator
        ));

        $classInfo = $reflector->reflect('TraitFixtureC');

        self::assertTrue($classInfo->hasMethod('a'));
        self::assertTrue($classInfo->hasMethod('a_protected'));

        $a          = $classInfo->getMethod('a');
        $aProtected = $classInfo->getMethod('a_protected');

        self::assertSame('a', $a->getName());
        self::assertSame($a->getName(), $aProtected->getName());
        self::assertSame('TraitFixtureTraitC', $a->getDeclaringClass()->getName());
        self::assertSame('TraitFixtureTraitC', $aProtected->getDeclaringClass()->getName());

        self::assertTrue($classInfo->hasMethod('b'));
        self::assertTrue($classInfo->hasMethod('b_renamed'));

        $b        = $classInfo->getMethod('b');
        $bRenamed = $classInfo->getMethod('b_renamed');

        self::assertSame('b', $b->getName());
        self::assertSame($b->getName(), $bRenamed->getName());
        self::assertSame('TraitFixtureTraitC', $b->getDeclaringClass()->getName());
        self::assertSame('TraitFixtureTraitC', $bRenamed->getDeclaringClass()->getName());

        self::assertTrue($classInfo->hasMethod('c'));

        $c = $classInfo->getMethod('c');

        self::assertSame('c', $c->getName());
        self::assertSame('TraitFixtureTraitC', $c->getDeclaringClass()->getName());
    }

    public function testMethodsFromTraitsWithConflicts() : void
    {
        $reflector = new ClassReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/TraitFixture.php',
            $this->astLocator
        ));

        $classInfo = $reflector->reflect('TraitFixtureD');

        self::assertTrue($classInfo->hasMethod('boo'));
        self::assertSame('TraitFixtureD', $classInfo->getMethod('boo')->getDeclaringClass()->getName());

        self::assertTrue($classInfo->hasMethod('foo'));

        $foo = $classInfo->getMethod('foo');

        self::assertSame('TraitFixtureTraitD1', $foo->getDeclaringClass()->getName());
        self::assertSame('TraitFixtureD', $foo->getImplementingClass()->getName());

        self::assertTrue($classInfo->hasMethod('hoo'));
        self::assertTrue($classInfo->hasMethod('hooFirstAlias'));
        self::assertTrue($classInfo->hasMethod('hooSecondAlias'));

        $hoo            = $classInfo->getMethod('hoo');
        $hooFirstAlias  = $classInfo->getMethod('hooFirstAlias');
        $hooSecondAlias = $classInfo->getMethod('hooSecondAlias');

        self::assertSame('TraitFixtureTraitD1', $hoo->getDeclaringClass()->getName());
        self::assertSame('TraitFixtureTraitD1', $hooFirstAlias->getDeclaringClass()->getName());
        self::assertSame('TraitFixtureTraitD1', $hooSecondAlias->getDeclaringClass()->getName());
        self::assertSame('TraitFixtureD', $hoo->getImplementingClass()->getName());
        self::assertSame('TraitFixtureD', $hooFirstAlias->getImplementingClass()->getName());
        self::assertSame('TraitFixtureD', $hooSecondAlias->getImplementingClass()->getName());
    }

    public function testGetInterfaceNames() : void
    {
        $reflector = new ClassReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/ClassWithInterfaces.php',
            $this->astLocator
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
                ->reflect(ClassWithInterfaces\ExampleClass::class)
                ->getInterfaceNames(),
            'Interfaces are retrieved in the correct numeric order (indexed by number)'
        );
    }

    public function testGetInterfaces() : void
    {
        $reflector  = new ClassReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/ClassWithInterfaces.php',
            $this->astLocator
        ));
        $interfaces = $reflector
                ->reflect(ClassWithInterfaces\ExampleClass::class)
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

    public function testGetInterfaceNamesWillReturnAllInheritedInterfaceImplementationsOnASubclass() : void
    {
        $reflector = new ClassReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/ClassWithInterfaces.php',
            $this->astLocator
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
                ->reflect(ClassWithInterfaces\SubExampleClass::class)
                ->getInterfaceNames(),
            'Child class interfaces are retrieved in the correct numeric order (indexed by number)'
        );
    }

    public function testGetInterfacesWillReturnAllInheritedInterfaceImplementationsOnASubclass() : void
    {
        $reflector = new ClassReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/ClassWithInterfaces.php',
            $this->astLocator
        ));

        $interfaces = $reflector
            ->reflect(ClassWithInterfaces\SubExampleClass::class)
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

    public function testGetInterfaceNamesWillConsiderMultipleInheritanceLevelsAndImplementsOrderOverrides() : void
    {
        $reflector = new ClassReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/ClassWithInterfaces.php',
            $this->astLocator
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
                ->reflect(ClassWithInterfaces\SubSubExampleClass::class)
                ->getInterfaceNames(),
            'Child class interfaces are retrieved in the correct numeric order (indexed by number)'
        );
    }

    public function testGetInterfacesWillConsiderMultipleInheritanceLevels() : void
    {
        $reflector  = new ClassReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/ClassWithInterfaces.php',
            $this->astLocator
        ));
        $interfaces = $reflector
            ->reflect(ClassWithInterfaces\SubSubExampleClass::class)
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

    public function testGetInterfacesWillConsiderInterfaceInheritanceLevels() : void
    {
        $reflector  = new ClassReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/ClassWithInterfaces.php',
            $this->astLocator
        ));
        $interfaces = $reflector
            ->reflect(ClassWithInterfaces\ExampleImplementingCompositeInterface::class)
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

    public function testIsInstance() : void
    {
        // note: ClassForHinting is safe to type-check against, as it will actually be loaded at runtime
        $class = (new ClassReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/ClassForHinting.php',
            $this->astLocator
        )))->reflect(ClassForHinting::class);

        self::assertFalse($class->isInstance(new stdClass()));
        self::assertFalse($class->isInstance($this));
        self::assertTrue($class->isInstance(new ClassForHinting()));

        $this->expectException(NotAnObject::class);

        $class->isInstance('foo');
    }

    public function testIsSubclassOf() : void
    {
        $subExampleClass = (new ClassReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/ClassWithInterfaces.php',
            $this->astLocator
        )))->reflect(ClassWithInterfaces\SubExampleClass::class);

        self::assertFalse(
            $subExampleClass->isSubclassOf(ClassWithInterfaces\SubExampleClass::class),
            'Not a subclass of itself'
        );
        self::assertFalse(
            $subExampleClass->isSubclassOf(ClassWithInterfaces\SubSubExampleClass::class),
            'Not a subclass of a child class'
        );
        self::assertFalse(
            $subExampleClass->isSubclassOf(stdClass::class),
            'Not a subclass of a unrelated'
        );
        self::assertTrue(
            $subExampleClass->isSubclassOf(ClassWithInterfaces\ExampleClass::class),
            'A subclass of a parent class'
        );
        self::assertTrue(
            $subExampleClass->isSubclassOf('\\' . ClassWithInterfaces\ExampleClass::class),
            'A subclass of a parent class (considering eventual backslashes upfront)'
        );
    }

    public function testImplementsInterface() : void
    {
        $subExampleClass = (new ClassReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/ClassWithInterfaces.php',
            $this->astLocator
        )))->reflect(ClassWithInterfaces\SubExampleClass::class);

        self::assertTrue($subExampleClass->implementsInterface(ClassWithInterfaces\A::class));
        self::assertFalse($subExampleClass->implementsInterface(ClassWithInterfaces\B::class));
        self::assertTrue($subExampleClass->implementsInterface(ClassWithInterfacesOther\B::class));
        self::assertTrue($subExampleClass->implementsInterface(ClassWithInterfaces\C::class));
        self::assertTrue($subExampleClass->implementsInterface(ClassWithInterfacesOther\D::class));
        self::assertTrue($subExampleClass->implementsInterface(E::class));
        self::assertFalse($subExampleClass->implementsInterface(Iterator::class));
    }

    public function testIsInstantiable() : void
    {
        $reflector = new ClassReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/ExampleClass.php',
            $this->astLocator
        ));

        self::assertTrue($reflector->reflect(ExampleClass::class)->isInstantiable());
        self::assertTrue($reflector->reflect(Fixture\ClassWithParent::class)->isInstantiable());
        self::assertTrue($reflector->reflect(FinalClass::class)->isInstantiable());
        self::assertFalse($reflector->reflect(ExampleTrait::class)->isInstantiable());
        self::assertFalse($reflector->reflect(AbstractClass::class)->isInstantiable());
        self::assertFalse($reflector->reflect(ExampleInterface::class)->isInstantiable());

        $reflector = new ClassReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/ClassesWithPublicOrNonPublicContructor.php',
            $this->astLocator
        ));

        self::assertTrue($reflector->reflect(ClassWithPublicConstructor::class)->isInstantiable());
        self::assertTrue($reflector->reflect(ClassWithoutConstructor::class)->isInstantiable());
        self::assertFalse($reflector->reflect(ClassWithPrivateConstructor::class)->isInstantiable());
        self::assertFalse($reflector->reflect(ClassWithProtectedConstructor::class)->isInstantiable());
        self::assertFalse($reflector->reflect(ClassWithExtendedConstructor::class)->isInstantiable());
    }

    public function testIsCloneable() : void
    {
        $reflector = new ClassReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/ExampleClass.php',
            $this->astLocator
        ));

        self::assertTrue($reflector->reflect(ExampleClass::class)->isCloneable());
        self::assertTrue($reflector->reflect(Fixture\ClassWithParent::class)->isCloneable());
        self::assertTrue($reflector->reflect(FinalClass::class)->isCloneable());
        self::assertFalse($reflector->reflect(ExampleTrait::class)->isCloneable());
        self::assertFalse($reflector->reflect(AbstractClass::class)->isCloneable());
        self::assertFalse($reflector->reflect(ExampleInterface::class)->isCloneable());

        $reflector = new ClassReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/ClassesWithCloneMethod.php',
            $this->astLocator
        ));

        self::assertTrue($reflector->reflect(ClassesWithCloneMethod\WithPublicClone::class)->isCloneable());
        self::assertFalse($reflector->reflect(ClassesWithCloneMethod\WithProtectedClone::class)->isCloneable());
        self::assertFalse($reflector->reflect(ClassesWithCloneMethod\WithPrivateClone::class)->isCloneable());
    }

    public function testIsIterateable() : void
    {
        $reflector = new ClassReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/ClassesImplementingIterators.php',
            $this->astLocator
        ));

        self::assertTrue(
            $reflector
                ->reflect(ClassesImplementingIterators\TraversableImplementation::class)
                ->isIterateable()
        );
        self::assertFalse(
            $reflector
                ->reflect(ClassesImplementingIterators\NonTraversableImplementation::class)
                ->isIterateable()
        );
        self::assertFalse(
            $reflector
                ->reflect(ClassesImplementingIterators\AbstractTraversableImplementation::class)
                ->isIterateable()
        );
        self::assertFalse(
            $reflector
                ->reflect(ClassesImplementingIterators\TraversableExtension::class)
                ->isIterateable()
        );
    }

    public function testGetParentClassesFailsWithClassExtendingFromInterface() : void
    {
        $reflector = new ClassReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/InvalidInheritances.php',
            $this->astLocator
        ));

        $class = $reflector->reflect(InvalidInheritances\ClassExtendingInterface::class);

        $this->expectException(NotAClassReflection::class);

        $class->getParentClass();
    }

    public function testGetParentClassesFailsWithClassExtendingFromTrait() : void
    {
        $reflector = new ClassReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/InvalidInheritances.php',
            $this->astLocator
        ));

        $class = $reflector->reflect(InvalidInheritances\ClassExtendingTrait::class);

        $this->expectException(NotAClassReflection::class);

        $class->getParentClass();
    }

    public function testGetInterfacesFailsWithInterfaceExtendingFromClass() : void
    {
        $reflector = new ClassReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/InvalidInheritances.php',
            $this->astLocator
        ));

        $class = $reflector->reflect(InvalidInheritances\InterfaceExtendingClass::class);

        $this->expectException(NotAnInterfaceReflection::class);

        $class->getInterfaces();
    }

    public function testGetInterfacesFailsWithInterfaceExtendingFromTrait() : void
    {
        $reflector = new ClassReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/InvalidInheritances.php',
            $this->astLocator
        ));

        $class = $reflector->reflect(InvalidInheritances\InterfaceExtendingTrait::class);

        $this->expectException(NotAnInterfaceReflection::class);

        $class->getInterfaces();
    }

    public function testGetImmediateInterfaces() : void
    {
        $reflector = new ClassReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/PrototypeTree.php',
            $this->astLocator
        ));

        $interfaces = $reflector->reflect('Boom\B')->getImmediateInterfaces();

        self::assertCount(1, $interfaces);
        self::assertInstanceOf(ReflectionClass::class, $interfaces['Boom\Bar']);
        self::assertSame('Boom\Bar', $interfaces['Boom\Bar']->getName());
    }

    public function testGetImmediateInterfacesDoesNotIncludeCurrentInterface() : void
    {
        $reflector = new ClassReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/ClassWithInterfaces.php',
            $this->astLocator
        ));

        $cInterfaces = array_map(
            static function (ReflectionClass $interface) : string {
                return $interface->getShortName();
            },
            $reflector->reflect(ClassWithInterfacesExtendingInterfaces\C::class)->getImmediateInterfaces()
        );
        $dInterfaces = array_map(
            static function (ReflectionClass $interface) : string {
                return $interface->getShortName();
            },
            $reflector->reflect(ClassWithInterfacesExtendingInterfaces\D::class)->getImmediateInterfaces()
        );

        sort($cInterfaces);
        sort($dInterfaces);

        self::assertSame(['B'], $cInterfaces);
        self::assertSame(['A', 'B', 'C'], $dInterfaces);
    }

    public function testReflectedTraitHasNoInterfaces() : void
    {
        $reflector = new ClassReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/TraitFixture.php',
            $this->astLocator
        ));

        $traitReflection = $reflector->reflect('TraitFixtureTraitA');
        self::assertSame([], $traitReflection->getInterfaces());
    }

    public function testToString() : void
    {
        $reflection = ReflectionClass::createFromName(ExampleClass::class);
        self::assertStringMatchesFormat(
            file_get_contents(__DIR__ . '/../Fixture/ExampleClassExport.txt'),
            $reflection->__toString()
        );
    }

    public function testCannotClone() : void
    {
        $reflector = new ClassReflector($this->getComposerLocator());
        $classInfo = $reflector->reflect(ExampleClass::class);

        $this->expectException(Uncloneable::class);
        $unused = clone $classInfo;
    }

    public function testGetStaticProperties() : void
    {
        $staticPropertiesFixtureFile = __DIR__ . '/../Fixture/StaticProperties.php';
        require_once $staticPropertiesFixtureFile;

        $classInfo = (new ClassReflector(new SingleFileSourceLocator($staticPropertiesFixtureFile, $this->astLocator)))
            ->reflect(StaticProperties::class);

        $expectedStaticProperties = [
            'parentBaz' => 'parentBaz',
            'parentBat' => 456,
            'baz' => 'baz',
            'bat' => 123,
            'qux' => null,
        ];

        self::assertSame($expectedStaticProperties, $classInfo->getStaticProperties());
    }

    public function testGetStaticPropertyValueThrowsExceptionWhenPropertyDoesNotExist() : void
    {
        $staticPropertyGetSetFixtureFile = __DIR__ . '/../Fixture/StaticPropertyGetSet.php';
        require_once $staticPropertyGetSetFixtureFile;

        $classInfo = (new ClassReflector(new SingleFileSourceLocator($staticPropertyGetSetFixtureFile, $this->astLocator)))
            ->reflect(StaticPropertyGetSet::class);

        $this->expectException(PropertyDoesNotExist::class);
        $classInfo->getStaticPropertyValue('foo');
    }

    public function testSetStaticPropertyValueThrowsExceptionWhenPropertyDoesNotExist() : void
    {
        $staticPropertyGetSetFixtureFile = __DIR__ . '/../Fixture/StaticPropertyGetSet.php';
        require_once $staticPropertyGetSetFixtureFile;

        $classInfo = (new ClassReflector(new SingleFileSourceLocator($staticPropertyGetSetFixtureFile, $this->astLocator)))
            ->reflect(StaticPropertyGetSet::class);

        $this->expectException(PropertyDoesNotExist::class);
        $classInfo->setStaticPropertyValue('foo', null);
    }

    public function testGetAst() : void
    {
        $php = '<?php
            class Foo {}
        ';

        $reflection = (new ClassReflector(new StringSourceLocator($php, $this->astLocator)))->reflect('Foo');

        $ast = $reflection->getAst();

        self::assertInstanceOf(Class_::class, $ast);
        self::assertSame('Foo', $ast->name->name);
    }

    public function testSetIsFinal() : void
    {
        $php = '<?php
            final class Foo {}
        ';

        $reflection = (new ClassReflector(new StringSourceLocator($php, $this->astLocator)))->reflect('Foo');

        self::assertTrue($reflection->isFinal());

        $reflection->setFinal(false);
        self::assertFalse($reflection->isFinal());

        $reflection->setFinal(true);
        self::assertTrue($reflection->isFinal());
    }

    public function testSetIsFinalThrowsExceptionForInterface() : void
    {
        $php = '<?php
            interface Foo {}
        ';

        $reflection = (new ClassReflector(new StringSourceLocator($php, $this->astLocator)))->reflect('Foo');

        $this->expectException(NotAClassReflection::class);
        $reflection->setFinal(true);
    }

    public function testRemoveMethod() : void
    {
        $php = '<?php
            class Foo {
                public function bar() {}
            }
        ';

        $reflection = (new ClassReflector(new StringSourceLocator($php, $this->astLocator)))->reflect('Foo');

        self::assertTrue($reflection->hasMethod('bar'));

        $reflection->removeMethod('bar');

        self::assertFalse($reflection->hasMethod('bar'));
    }

    public function testAddMethod() : void
    {
        $php = '<?php
            class Foo {
            }
        ';

        $reflection = (new ClassReflector(new StringSourceLocator($php, $this->astLocator)))->reflect('Foo');

        self::assertFalse($reflection->hasMethod('bar'));

        $reflection->addMethod('bar');

        self::assertTrue($reflection->hasMethod('bar'));
    }

    public function testRemoveProperty() : void
    {
        $php = '<?php
            class Foo {
                public $bar;
            }
        ';

        $reflection = (new ClassReflector(new StringSourceLocator($php, $this->astLocator)))->reflect('Foo');

        self::assertTrue($reflection->hasProperty('bar'));

        $reflection->removeProperty('bar');

        self::assertFalse($reflection->hasProperty('bar'));
    }

    public function testAddProperty() : void
    {
        $php = '<?php
            class Foo {
            }
        ';

        $reflection = (new ClassReflector(new StringSourceLocator($php, $this->astLocator)))->reflect('Foo');

        self::assertFalse($reflection->hasProperty('bar'));

        $reflection->addProperty('publicBar', CoreReflectionProperty::IS_PUBLIC);
        self::assertTrue($reflection->hasProperty('publicBar'));
        self::assertTrue($reflection->getProperty('publicBar')->isPublic());

        $reflection->addProperty('protectedBar', CoreReflectionProperty::IS_PROTECTED);
        self::assertTrue($reflection->hasProperty('protectedBar'));
        self::assertTrue($reflection->getProperty('protectedBar')->isProtected());

        $reflection->addProperty('privateBar', CoreReflectionProperty::IS_PRIVATE);
        self::assertTrue($reflection->hasProperty('privateBar'));
        self::assertTrue($reflection->getProperty('privateBar')->isPrivate());

        $reflection->addProperty('staticBar', CoreReflectionProperty::IS_PUBLIC, true);
        self::assertTrue($reflection->hasProperty('staticBar'));
        self::assertTrue($reflection->getProperty('staticBar')->isStatic());
    }

    public function testGetConstantsReturnsAllConstantsRegardlessOfVisibility() : void
    {
        $php = '<?php
            class Foo {
                private const BAR_PRIVATE = 1;
                protected const BAR_PROTECTED = 2;
                public const BAR_PUBLIC = 3;
                const BAR_DEFAULT = 4;
            }
        ';

        $reflection = (new ClassReflector(new StringSourceLocator($php, $this->astLocator)))->reflect('Foo');

        $expectedConstants = [
            'BAR_PRIVATE' => 1,
            'BAR_PROTECTED' => 2,
            'BAR_PUBLIC' => 3,
            'BAR_DEFAULT' => 4,
        ];

        self::assertSame($expectedConstants, $reflection->getConstants());

        array_walk(
            $expectedConstants,
            static function ($constantValue, string $constantName) use ($reflection) : void {
                self::assertTrue($reflection->hasConstant($constantName), 'Constant ' . $constantName . ' not set');
                self::assertSame(
                    $constantValue,
                    $reflection->getConstant($constantName),
                    'Constant value for ' . $constantName . ' does not match'
                );
            }
        );
    }

    public function testGetConstantsReturnsInheritedConstants() : void
    {
        $classInfo = (new ClassReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/InheritedClassConstants.php',
            $this->astLocator
        )))->reflect('Next');

        $expectedConstants = [
            'F' => 'ff',
            'D' => 'dd',
            'C' => 'c',
            'A' => 'a',
            'B' => 'b',
        ];

        self::assertSame($expectedConstants, $classInfo->getConstants());
    }

    public function testGetImmediateConstants() : void
    {
        $classInfo = (new ClassReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/InheritedClassConstants.php',
            $this->astLocator
        )))->reflect('Next');

        self::assertSame(['F' => 'ff'], $classInfo->getImmediateConstants());
    }

    public function testGetReflectionConstantsReturnsInheritedConstants() : void
    {
        $classInfo = (new ClassReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/InheritedClassConstants.php',
            $this->astLocator
        )))->reflect('Next');

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
            static function ($constantValue, string $constantName) use ($reflectionConstants) : void {
                self::assertArrayHasKey($constantName, $reflectionConstants, 'Constant ' . $constantName . ' not set');
                self::assertSame(
                    $constantValue,
                    $reflectionConstants[$constantName]->getValue(),
                    'Constant value for ' . $constantName . ' does not match'
                );
            }
        );
    }

    public function testGetImmediateReflectionConstants() : void
    {
        $classInfo = (new ClassReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/InheritedClassConstants.php',
            $this->astLocator
        )))->reflect('Next');

        $reflectionConstants = $classInfo->getImmediateReflectionConstants();

        self::assertCount(1, $reflectionConstants);
        self::assertArrayHasKey('F', $reflectionConstants);
        self::assertInstanceOf(ReflectionClassConstant::class, $reflectionConstants['F']);
        self::assertSame('ff', $reflectionConstants['F']->getValue());
    }

    public function testGetConstantsDeclaredWithOneKeyword() : void
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

        $classInfo = (new ClassReflector(new StringSourceLocator($php, $this->astLocator)))->reflect('Foo');

        $constants = $classInfo->getConstants();

        self::assertCount(2, $constants);
        self::assertSame($expectedConstants, $constants);
    }
}
