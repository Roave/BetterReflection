<?php
declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection;

use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use Roave\BetterReflection\Fixture\StaticPropertyGetSet;
use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\Identifier\IdentifierType;
use Roave\BetterReflection\Reflection\Exception\ClassDoesNotExist;
use Roave\BetterReflection\Reflection\Exception\NotAClassReflection;
use Roave\BetterReflection\Reflection\Exception\NotAnInterfaceReflection;
use Roave\BetterReflection\Reflection\Exception\NotAnObject;
use Roave\BetterReflection\Reflection\Exception\PropertyDoesNotExist;
use Roave\BetterReflection\Reflection\Exception\PropertyNotPublic;
use Roave\BetterReflection\Reflection\Exception\Uncloneable;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionClassConstant;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use Roave\BetterReflection\Reflection\ReflectionProperty;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\SourceLocator\Type\ComposerSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;
use Roave\BetterReflectionTest\ClassesImplementingIterators;
use Roave\BetterReflectionTest\ClassesWithCloneMethod;
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
use Roave\BetterReflectionTest\Fixture\UpperCaseConstructDestruct;
use Roave\BetterReflectionTest\FixtureOther\AnotherClass;

/**
 * @covers \Roave\BetterReflection\Reflection\ReflectionClass
 */
class ReflectionClassTest extends \PHPUnit\Framework\TestCase
{
    private function getComposerLocator() : ComposerSourceLocator
    {
        global $loader;
        return new ComposerSourceLocator($loader);
    }

    public function testCanReflectInternalClassWithDefaultLocator() : void
    {
        self::assertSame(\stdClass::class, ReflectionClass::createFromName(\stdClass::class)->getName());
    }

    public function testCanReflectInstance() : void
    {
        $instance = new \stdClass();
        self::assertSame(\stdClass::class, ReflectionClass::createFromInstance($instance)->getName());
    }

    public function testCreateFromInstanceThrowsExceptionWhenInvalidArgumentProvided() : void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Instance must be an instance of an object');
        ReflectionClass::createFromInstance('invalid argument');
    }

    public function testCanReflectEvaledClassWithDefaultLocator() : void
    {
        $className = \uniqid('foo', false);

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
        $reflector = new ClassReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/NoNamespace.php'));
        $classInfo = $reflector->reflect('ClassWithNoNamespace');

        self::assertFalse($classInfo->inNamespace());
        self::assertSame('ClassWithNoNamespace', $classInfo->getName());
        self::assertSame('', $classInfo->getNamespaceName());
        self::assertSame('ClassWithNoNamespace', $classInfo->getShortName());
    }

    public function testClassNameMethodsWithExplicitGlobalNamespace() : void
    {
        $reflector = new ClassReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/ExampleClass.php'));
        $classInfo = $reflector->reflect('ClassWithExplicitGlobalNamespace');

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
        self::assertFalse(\class_exists(ExampleClass::class, false));

        $reflector = new ClassReflector($this->getComposerLocator());
        $reflector->reflect(ExampleClass::class);

        self::assertFalse(\class_exists(ExampleClass::class, false));
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
            [\ReflectionMethod::IS_STATIC, 1],
            [\ReflectionMethod::IS_ABSTRACT, 1],
            [\ReflectionMethod::IS_FINAL, 1],
            [\ReflectionMethod::IS_PUBLIC, 16],
            [\ReflectionMethod::IS_PROTECTED, 1],
            [\ReflectionMethod::IS_PRIVATE, 1],
            [
                \ReflectionMethod::IS_STATIC |
                \ReflectionMethod::IS_ABSTRACT |
                \ReflectionMethod::IS_FINAL |
                \ReflectionMethod::IS_PUBLIC |
                \ReflectionMethod::IS_PROTECTED |
                \ReflectionMethod::IS_PRIVATE,
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
        $reflector = new ClassReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/InheritedClassMethods.php'));
        $classInfo = $reflector->reflect('Qux');

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

    public function testGetImmediateMethods() : void
    {
        $reflector = new ClassReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/InheritedClassMethods.php'));

        $methods = $reflector->reflect('Qux')->getImmediateMethods();

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

    public function testGetReflectionConstants()
    {
        $reflector = new ClassReflector($this->getComposerLocator());
        $classInfo = $reflector->reflect(ExampleClass::class);
        $this->assertCount(5, $classInfo->getReflectionConstants());
    }

    public function testGetReflectionConstant()
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
        $reflector   = new ClassReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/Php4StyleConstruct.php'));
        $classInfo   = $reflector->reflect(\Php4StyleConstruct::class);
        $constructor = $classInfo->getConstructor();

        self::assertInstanceOf(ReflectionMethod::class, $constructor);
        self::assertTrue($constructor->isConstructor());
    }

    public function testGetConstructorWhenPhp4StyleInNamespace() : void
    {
        self::expectException(\OutOfBoundsException::class);

        $reflector = new ClassReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/Php4StyleConstructInNamespace.php'));
        $classInfo = $reflector->reflect(Fixture\Php4StyleConstructInNamespace::class);
        $classInfo->getConstructor();
    }

    public function testGetConstructorWhenPhp4StyleCaseInsensitive() : void
    {
        $reflector   = new ClassReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/Php4StyleCaseInsensitiveConstruct.php'));
        $classInfo   = $reflector->reflect(\Php4StyleCaseInsensitiveConstruct::class);
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

    public function getPropertiesWithFilterDataProvider() : array
    {
        return [
            [\ReflectionProperty::IS_STATIC, 1],
            [\ReflectionProperty::IS_PUBLIC, 2],
            [\ReflectionProperty::IS_PROTECTED, 1],
            [\ReflectionProperty::IS_PRIVATE, 1],
            [
                \ReflectionProperty::IS_STATIC |
                \ReflectionProperty::IS_PUBLIC |
                \ReflectionProperty::IS_PROTECTED |
                \ReflectionProperty::IS_PRIVATE,
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
        $reflector = new ClassReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/InheritedClassProperties.php'));
        $classInfo = $reflector->reflect(\Qux::class);

        $properties = $classInfo->getProperties();
        self::assertCount(5, $properties);
        self::assertContainsOnlyInstancesOf(ReflectionProperty::class, $properties);

        self::assertSame('a', $classInfo->getProperty('a')->getName(), 'Failed asserting that property a from trait Bar was returned');
        self::assertSame(\Bar::class, $classInfo->getProperty('a')->getDeclaringClass()->getName());

        self::assertSame('b', $classInfo->getProperty('b')->getName(), 'Failed asserting that private property b from trait Bar was returned');
        self::assertSame(\Bar::class, $classInfo->getProperty('b')->getDeclaringClass()->getName());

        self::assertSame('c', $classInfo->getProperty('c')->getName(), 'Failed asserting that public property c from parent class Baz was returned');
        self::assertSame(\Baz::class, $classInfo->getProperty('c')->getDeclaringClass()->getName());

        self::assertSame('d', $classInfo->getProperty('d')->getName(), 'Failed asserting that protected property d from parent class Baz was returned');
        self::assertSame(\Baz::class, $classInfo->getProperty('d')->getDeclaringClass()->getName());

        self::assertSame('f', $classInfo->getProperty('f')->getName(), 'Failed asserting that property f from SUT was returned');
        self::assertSame(\Qux::class, $classInfo->getProperty('f')->getDeclaringClass()->getName());
    }

    public function testGetImmediateProperties() : void
    {
        $reflector = new ClassReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/InheritedClassProperties.php'));
        $classInfo = $reflector->reflect(\Qux::class);

        $properties = $classInfo->getImmediateProperties();
        self::assertCount(1, $properties);
        self::assertContainsOnlyInstancesOf(ReflectionProperty::class, $properties);

        self::assertSame('f', $classInfo->getProperty('f')->getName(), 'Failed asserting that property f from SUT was returned');
        self::assertSame(\Qux::class, $classInfo->getProperty('f')->getDeclaringClass()->getName());
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

        self::assertSame('ExampleClass.php', \basename($detectedFilename));
    }

    public function testStaticCreation() : void
    {
        $reflection = ReflectionClass::createFromName(ExampleClass::class);
        self::assertSame('ExampleClass', $reflection->getShortName());
    }

    public function testGetParentClassDefault() : void
    {
        $reflector       = new ClassReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/ExampleClass.php'));
        $childReflection = $reflector->reflect(Fixture\ClassWithParent::class);

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
        $reflector       = new ClassReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/ExampleClass.php'));
        $childReflection = $reflector->reflect(Fixture\ClassWithTwoParents::class);

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
     * @param string $php
     * @param int $expectedStart
     * @param int $expectedEnd
     * @dataProvider startEndLineProvider
     */
    public function testStartEndLine(string $php, int $expectedStart, int $expectedEnd) : void
    {
        $reflector = new ClassReflector(new StringSourceLocator($php));
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
     * @param string $php
     * @param int $expectedStart
     * @param int $expectedEnd
     * @dataProvider columnsProvider
     */
    public function testGetStartColumnAndEndColumn(string $php, int $startColumn, int $endColumn) : void
    {
        $reflector = new ClassReflector(new StringSourceLocator($php));
        $classInfo = $reflector->reflect('Foo');

        self::assertSame($startColumn, $classInfo->getStartColumn());
        self::assertSame($endColumn, $classInfo->getEndColumn());
    }

    public function testGetDocComment() : void
    {
        $reflector = new ClassReflector($this->getComposerLocator());
        $classInfo = $reflector->reflect(ExampleClass::class);

        self::assertContains('Some comments here', $classInfo->getDocComment());
    }

    public function testGetDocCommentBetweeenComments() : void
    {
        $php       = '<?php
            /* A comment */
            /** Class description */
            /* An another comment */
            class Bar implements Foo {}
        ';
        $reflector = (new ClassReflector(new StringSourceLocator($php)))->reflect('Bar');

        self::assertContains('Class description', $reflector->getDocComment());
    }

    public function testGetDocCommentReturnsEmptyStringWithNoComment() : void
    {
        $reflector = new ClassReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/ExampleClass.php'));
        $classInfo = $reflector->reflect(AnotherClass::class);

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
        $classInfo = (new ClassReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/DefaultProperties.php')))->reflect('Foo');

        self::assertSame([
            'hasDefault' => 123,
            'noDefault' => null,
        ], $classInfo->getDefaultProperties());
    }

    public function testIsAnonymousWithNotAnonymousClass() : void
    {
        $reflector = new ClassReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/ExampleClass.php'));

        $classInfo = $reflector->reflect(ExampleClass::class);
        self::assertFalse($classInfo->isAnonymous());
    }

    public function testIsAnonymousWithAnonymousClassNoNamespace() : void
    {
        $reflector = new ClassReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/AnonymousClassNoNamespace.php'));

        $allClassesInfo = $reflector->getAllClasses();
        self::assertCount(1, $allClassesInfo);

        $classInfo = $allClassesInfo[0];
        self::assertTrue($classInfo->isAnonymous());
        self::assertFalse($classInfo->inNamespace());
        self::assertStringStartsWith(ReflectionClass::ANONYMOUS_CLASS_NAME_PREFIX, $classInfo->getName());
        self::assertStringEndsWith('Fixture/AnonymousClassNoNamespace.php(3)', $classInfo->getName());
    }

    public function testIsAnonymousWithAnonymousClassInNamespace() : void
    {
        $reflector = new ClassReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/AnonymousClassInNamespace.php'));

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
        $reflector = new ClassReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/NestedAnonymousClassInstances.php'));

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

        $reflector = new ClassReflector(new StringSourceLocator($php));

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
    }

    public function testIsInternalWithInternalClass() : void
    {
        $reflector = ClassReflector::buildDefaultReflector();
        $classInfo = $reflector->reflect('stdClass');

        self::assertTrue($classInfo->isInternal());
        self::assertFalse($classInfo->isUserDefined());
    }

    public function testIsAbstract() : void
    {
        $reflector = new ClassReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/ExampleClass.php'));

        $classInfo = $reflector->reflect(AbstractClass::class);
        self::assertTrue($classInfo->isAbstract());

        $classInfo = $reflector->reflect(ExampleClass::class);
        self::assertFalse($classInfo->isAbstract());
    }

    public function testIsFinal() : void
    {
        $reflector = new ClassReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/ExampleClass.php'));

        $classInfo = $reflector->reflect(FinalClass::class);
        self::assertTrue($classInfo->isFinal());

        $classInfo = $reflector->reflect(ExampleClass::class);
        self::assertFalse($classInfo->isFinal());
    }

    public function modifierProvider() : array
    {
        return [
            ['ExampleClass', 0, []],
            ['AbstractClass', \ReflectionClass::IS_EXPLICIT_ABSTRACT, ['abstract']],
            ['FinalClass', \ReflectionClass::IS_FINAL, ['final']],
        ];
    }

    /**
     * @param string $className
     * @param int $expectedModifier
     * @param string[] $expectedModifierNames
     * @dataProvider modifierProvider
     */
    public function testGetModifiers(string $className, int $expectedModifier, array $expectedModifierNames) : void
    {
        $reflector = new ClassReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/ExampleClass.php'));
        $classInfo = $reflector->reflect('\Roave\BetterReflectionTest\Fixture\\' . $className);

        self::assertSame($expectedModifier, $classInfo->getModifiers());
        self::assertSame(
            $expectedModifierNames,
            \Reflection::getModifierNames($classInfo->getModifiers())
        );
    }

    public function testIsTrait() : void
    {
        $reflector = new ClassReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/ExampleClass.php'));

        $classInfo = $reflector->reflect(ExampleTrait::class);
        self::assertTrue($classInfo->isTrait());

        $classInfo = $reflector->reflect(ExampleClass::class);
        self::assertFalse($classInfo->isTrait());
    }

    public function testIsInterface() : void
    {
        $reflector = new ClassReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/ExampleClass.php'));

        $classInfo = $reflector->reflect(ExampleInterface::class);
        self::assertTrue($classInfo->isInterface());

        $classInfo = $reflector->reflect(ExampleClass::class);
        self::assertFalse($classInfo->isInterface());
    }

    public function testGetTraits() : void
    {
        $sourceLocator = new SingleFileSourceLocator(__DIR__ . '/../Fixture/TraitFixture.php');
        $reflector     = new ClassReflector($sourceLocator);

        $classInfo = $reflector->reflect('TraitFixtureA');
        $traits    = $classInfo->getTraits();

        self::assertCount(1, $traits);
        self::assertInstanceOf(ReflectionClass::class, $traits[0]);
        self::assertTrue($traits[0]->isTrait());
    }

    public function testGetTraitsReturnsEmptyArrayWhenNoTraitsUsed() : void
    {
        $sourceLocator = new SingleFileSourceLocator(__DIR__ . '/../Fixture/TraitFixture.php');
        $reflector     = new ClassReflector($sourceLocator);

        $classInfo = $reflector->reflect('TraitFixtureB');
        $traits    = $classInfo->getTraits();

        self::assertCount(0, $traits);
    }

    public function testGetTraitNames() : void
    {
        $sourceLocator = new SingleFileSourceLocator(__DIR__ . '/../Fixture/TraitFixture.php');

        self::assertSame(
            [
                'TraitFixtureTraitA',
            ],
            (new ClassReflector($sourceLocator))->reflect('TraitFixtureA')->getTraitNames()
        );
    }

    public function testGetTraitAliases() : void
    {
        $sourceLocator = new SingleFileSourceLocator(__DIR__ . '/../Fixture/TraitFixture.php');
        $reflector     = new ClassReflector($sourceLocator);

        $classInfo = $reflector->reflect('TraitFixtureC');

        self::assertSame([
            'a_protected' => 'TraitFixtureTraitC::a',
            'b_renamed' => 'TraitFixtureTraitC::b',
        ], $classInfo->getTraitAliases());
    }

    public function testGetInterfaceNames() : void
    {
        $sourceLocator = new SingleFileSourceLocator(__DIR__ . '/../Fixture/ClassWithInterfaces.php');

        self::assertSame(
            [
                ClassWithInterfaces\A::class,
                ClassWithInterfacesOther\B::class,
                ClassWithInterfaces\C::class,
                ClassWithInterfacesOther\D::class,
                \E::class,
            ],
            (new ClassReflector($sourceLocator))
                ->reflect(ClassWithInterfaces\ExampleClass::class)
                ->getInterfaceNames(),
            'Interfaces are retrieved in the correct numeric order (indexed by number)'
        );
    }

    public function testGetInterfaces() : void
    {
        $sourceLocator = new SingleFileSourceLocator(__DIR__ . '/../Fixture/ClassWithInterfaces.php');
        $interfaces    = (new ClassReflector($sourceLocator))
                ->reflect(ClassWithInterfaces\ExampleClass::class)
                ->getInterfaces();

        $expectedInterfaces = [
            ClassWithInterfaces\A::class,
            ClassWithInterfacesOther\B::class,
            ClassWithInterfaces\C::class,
            ClassWithInterfacesOther\D::class,
            \E::class,
        ];

        self::assertCount(\count($expectedInterfaces), $interfaces);

        foreach ($expectedInterfaces as $expectedInterface) {
            self::assertArrayHasKey($expectedInterface, $interfaces);
            self::assertInstanceOf(ReflectionClass::class, $interfaces[$expectedInterface]);
            self::assertSame($expectedInterface, $interfaces[$expectedInterface]->getName());
        }
    }

    public function testGetInterfaceNamesWillReturnAllInheritedInterfaceImplementationsOnASubclass() : void
    {
        $sourceLocator = new SingleFileSourceLocator(__DIR__ . '/../Fixture/ClassWithInterfaces.php');

        self::assertSame(
            [
                ClassWithInterfaces\A::class,
                ClassWithInterfacesOther\B::class,
                ClassWithInterfaces\C::class,
                ClassWithInterfacesOther\D::class,
                \E::class,
            ],
            (new ClassReflector($sourceLocator))
                ->reflect(ClassWithInterfaces\SubExampleClass::class)
                ->getInterfaceNames(),
            'Child class interfaces are retrieved in the correct numeric order (indexed by number)'
        );
    }

    public function testGetInterfacesWillReturnAllInheritedInterfaceImplementationsOnASubclass() : void
    {
        $sourceLocator = new SingleFileSourceLocator(__DIR__ . '/../Fixture/ClassWithInterfaces.php');
        $interfaces    = (new ClassReflector($sourceLocator))
            ->reflect(ClassWithInterfaces\SubExampleClass::class)
            ->getInterfaces();

        $expectedInterfaces = [
            ClassWithInterfaces\A::class,
            ClassWithInterfacesOther\B::class,
            ClassWithInterfaces\C::class,
            ClassWithInterfacesOther\D::class,
            \E::class,
        ];

        self::assertCount(\count($expectedInterfaces), $interfaces);

        foreach ($expectedInterfaces as $expectedInterface) {
            self::assertArrayHasKey($expectedInterface, $interfaces);
            self::assertInstanceOf(ReflectionClass::class, $interfaces[$expectedInterface]);
            self::assertSame($expectedInterface, $interfaces[$expectedInterface]->getName());
        }
    }

    public function testGetInterfaceNamesWillConsiderMultipleInheritanceLevelsAndImplementsOrderOverrides() : void
    {
        $sourceLocator = new SingleFileSourceLocator(__DIR__ . '/../Fixture/ClassWithInterfaces.php');

        self::assertSame(
            [
                ClassWithInterfaces\A::class,
                ClassWithInterfacesOther\B::class,
                ClassWithInterfaces\C::class,
                ClassWithInterfacesOther\D::class,
                \E::class,
                ClassWithInterfaces\B::class,
            ],
            (new ClassReflector($sourceLocator))
                ->reflect(ClassWithInterfaces\SubSubExampleClass::class)
                ->getInterfaceNames(),
            'Child class interfaces are retrieved in the correct numeric order (indexed by number)'
        );
    }

    public function testGetInterfacesWillConsiderMultipleInheritanceLevels() : void
    {
        $sourceLocator = new SingleFileSourceLocator(__DIR__ . '/../Fixture/ClassWithInterfaces.php');
        $interfaces    = (new ClassReflector($sourceLocator))
            ->reflect(ClassWithInterfaces\SubSubExampleClass::class)
            ->getInterfaces();

        $expectedInterfaces = [
            ClassWithInterfaces\A::class,
            ClassWithInterfacesOther\B::class,
            ClassWithInterfaces\C::class,
            ClassWithInterfacesOther\D::class,
            \E::class,
            ClassWithInterfaces\B::class,
        ];

        self::assertCount(\count($expectedInterfaces), $interfaces);

        foreach ($expectedInterfaces as $expectedInterface) {
            self::assertArrayHasKey($expectedInterface, $interfaces);
            self::assertInstanceOf(ReflectionClass::class, $interfaces[$expectedInterface]);
            self::assertSame($expectedInterface, $interfaces[$expectedInterface]->getName());
        }
    }

    public function testGetInterfacesWillConsiderInterfaceInheritanceLevels() : void
    {
        $sourceLocator = new SingleFileSourceLocator(__DIR__ . '/../Fixture/ClassWithInterfaces.php');
        $interfaces    = (new ClassReflector($sourceLocator))
            ->reflect(ClassWithInterfaces\ExampleImplementingCompositeInterface::class)
            ->getInterfaces();

        $expectedInterfaces = [
            ClassWithInterfacesExtendingInterfaces\D::class,
            ClassWithInterfacesExtendingInterfaces\C::class,
            ClassWithInterfacesExtendingInterfaces\B::class,
            ClassWithInterfacesExtendingInterfaces\A::class,
        ];

        self::assertCount(\count($expectedInterfaces), $interfaces);

        foreach ($expectedInterfaces as $expectedInterface) {
            self::assertArrayHasKey($expectedInterface, $interfaces);
            self::assertInstanceOf(ReflectionClass::class, $interfaces[$expectedInterface]);
            self::assertSame($expectedInterface, $interfaces[$expectedInterface]->getName());
        }
    }

    public function testIsInstance() : void
    {
        // note: ClassForHinting is safe to type-check against, as it will actually be loaded at runtime
        $class = (new ClassReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/ClassForHinting.php')))
            ->reflect(ClassForHinting::class);

        self::assertFalse($class->isInstance(new \stdClass()));
        self::assertFalse($class->isInstance($this));
        self::assertTrue($class->isInstance(new ClassForHinting()));

        $this->expectException(NotAnObject::class);

        $class->isInstance('foo');
    }

    public function testIsSubclassOf() : void
    {
        $sourceLocator   = new SingleFileSourceLocator(__DIR__ . '/../Fixture/ClassWithInterfaces.php');
        $subExampleClass = (new ClassReflector($sourceLocator))
            ->reflect(ClassWithInterfaces\SubExampleClass::class);

        self::assertFalse(
            $subExampleClass->isSubclassOf(ClassWithInterfaces\SubExampleClass::class),
            'Not a subclass of itself'
        );
        self::assertFalse(
            $subExampleClass->isSubclassOf(ClassWithInterfaces\SubSubExampleClass::class),
            'Not a subclass of a child class'
        );
        self::assertFalse(
            $subExampleClass->isSubclassOf(\stdClass::class),
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
        $sourceLocator   = new SingleFileSourceLocator(__DIR__ . '/../Fixture/ClassWithInterfaces.php');
        $subExampleClass = (new ClassReflector($sourceLocator))
            ->reflect(ClassWithInterfaces\SubExampleClass::class);

        self::assertTrue($subExampleClass->implementsInterface(ClassWithInterfaces\A::class));
        self::assertFalse($subExampleClass->implementsInterface(ClassWithInterfaces\B::class));
        self::assertTrue($subExampleClass->implementsInterface(ClassWithInterfacesOther\B::class));
        self::assertTrue($subExampleClass->implementsInterface(ClassWithInterfaces\C::class));
        self::assertTrue($subExampleClass->implementsInterface(ClassWithInterfacesOther\D::class));
        self::assertTrue($subExampleClass->implementsInterface(\E::class));
        self::assertFalse($subExampleClass->implementsInterface(\Iterator::class));
    }

    public function testIsInstantiable() : void
    {
        $reflector = new ClassReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/ExampleClass.php'));

        self::assertTrue($reflector->reflect(ExampleClass::class)->isInstantiable());
        self::assertTrue($reflector->reflect(Fixture\ClassWithParent::class)->isInstantiable());
        self::assertTrue($reflector->reflect(FinalClass::class)->isInstantiable());
        self::assertFalse($reflector->reflect(ExampleTrait::class)->isInstantiable());
        self::assertFalse($reflector->reflect(AbstractClass::class)->isInstantiable());
        self::assertFalse($reflector->reflect(ExampleInterface::class)->isInstantiable());
    }

    public function testIsCloneable() : void
    {
        $reflector = new ClassReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/ExampleClass.php'));

        self::assertTrue($reflector->reflect(ExampleClass::class)->isCloneable());
        self::assertTrue($reflector->reflect(Fixture\ClassWithParent::class)->isCloneable());
        self::assertTrue($reflector->reflect(FinalClass::class)->isCloneable());
        self::assertFalse($reflector->reflect(ExampleTrait::class)->isCloneable());
        self::assertFalse($reflector->reflect(AbstractClass::class)->isCloneable());
        self::assertFalse($reflector->reflect(ExampleInterface::class)->isCloneable());

        $reflector = new ClassReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/ClassesWithCloneMethod.php'
        ));

        self::assertTrue($reflector->reflect(ClassesWithCloneMethod\WithPublicClone::class)->isCloneable());
        self::assertFalse($reflector->reflect(ClassesWithCloneMethod\WithProtectedClone::class)->isCloneable());
        self::assertFalse($reflector->reflect(ClassesWithCloneMethod\WithPrivateClone::class)->isCloneable());
    }

    public function testIsIterateable() : void
    {
        $sourceLocator = new SingleFileSourceLocator(__DIR__ . '/../Fixture/ClassesImplementingIterators.php');
        $reflector     = new ClassReflector($sourceLocator);

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
        $sourceLocator = new SingleFileSourceLocator(__DIR__ . '/../Fixture/InvalidInheritances.php');
        $reflector     = new ClassReflector($sourceLocator);

        $class = $reflector->reflect(InvalidInheritances\ClassExtendingInterface::class);

        $this->expectException(NotAClassReflection::class);

        $class->getParentClass();
    }

    public function testGetParentClassesFailsWithClassExtendingFromTrait() : void
    {
        $sourceLocator = new SingleFileSourceLocator(__DIR__ . '/../Fixture/InvalidInheritances.php');
        $reflector     = new ClassReflector($sourceLocator);

        $class = $reflector->reflect(InvalidInheritances\ClassExtendingTrait::class);

        $this->expectException(NotAClassReflection::class);

        $class->getParentClass();
    }

    public function testGetInterfacesFailsWithInterfaceExtendingFromClass() : void
    {
        $sourceLocator = new SingleFileSourceLocator(__DIR__ . '/../Fixture/InvalidInheritances.php');
        $reflector     = new ClassReflector($sourceLocator);

        $class = $reflector->reflect(InvalidInheritances\InterfaceExtendingClass::class);

        $this->expectException(NotAnInterfaceReflection::class);

        $class->getInterfaces();
    }

    public function testGetInterfacesFailsWithInterfaceExtendingFromTrait() : void
    {
        $sourceLocator = new SingleFileSourceLocator(__DIR__ . '/../Fixture/InvalidInheritances.php');
        $reflector     = new ClassReflector($sourceLocator);

        $class = $reflector->reflect(InvalidInheritances\InterfaceExtendingTrait::class);

        $this->expectException(NotAnInterfaceReflection::class);

        $class->getInterfaces();
    }

    public function testGetImmediateInterfaces() : void
    {
        $reflector = new ClassReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/PrototypeTree.php'));

        $interfaces = $reflector->reflect('Boom\B')->getImmediateInterfaces();

        self::assertCount(1, $interfaces);
        self::assertInstanceOf(ReflectionClass::class, $interfaces['Boom\Bar']);
        self::assertSame('Boom\Bar', $interfaces['Boom\Bar']->getName());
    }

    public function testGetImmediateInterfacesDoesNotIncludeCurrentInterface() : void
    {
        $reflector = new ClassReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/ClassWithInterfaces.php'));

        $cInterfaces = \array_map(
            function (ReflectionClass $interface) : string {
                return $interface->getShortName();
            },
            $reflector->reflect(ClassWithInterfacesExtendingInterfaces\C::class)->getImmediateInterfaces()
        );
        $dInterfaces = \array_map(
            function (ReflectionClass $interface) : string {
                return $interface->getShortName();
            },
            $reflector->reflect(ClassWithInterfacesExtendingInterfaces\D::class)->getImmediateInterfaces()
        );

        \sort($cInterfaces);
        \sort($dInterfaces);

        self::assertSame(['B'], $cInterfaces);
        self::assertSame(['A', 'B', 'C'], $dInterfaces);
    }

    public function testReflectedTraitHasNoInterfaces() : void
    {
        $sourceLocator = new SingleFileSourceLocator(__DIR__ . '/../Fixture/TraitFixture.php');
        $reflector     = new ClassReflector($sourceLocator);

        $traitReflection = $reflector->reflect('TraitFixtureTraitA');
        self::assertSame([], $traitReflection->getInterfaces());
    }

    public function testFetchingFqsenThrowsExceptionWithNonObjectName() : void
    {
        $sourceLocator = new StringSourceLocator('<?php class Foo {}');
        $reflector     = new ClassReflector($sourceLocator);
        $identifier    = new Identifier('Foo', new IdentifierType(IdentifierType::IDENTIFIER_CLASS));
        $reflection    = $sourceLocator->locateIdentifier($reflector, $identifier);

        $reflectionClassReflection       = new \ReflectionClass(ReflectionClass::class);
        $reflectionClassMethodReflection = $reflectionClassReflection->getMethod('getFqsenFromNamedNode');
        $reflectionClassMethodReflection->setAccessible(true);

        $nameNode = new Name(['int']);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Unable to determine FQSEN for named node');
        $reflectionClassMethodReflection->invoke($reflection, $nameNode);
    }

    public function testClassToString() : void
    {
        $reflection = ReflectionClass::createFromName(ExampleClass::class);
        self::assertStringMatchesFormat(
            \file_get_contents(__DIR__ . '/../Fixture/ExampleClassExport.txt'),
            $reflection->__toString()
        );
    }

    public function testImplementsReflector() : void
    {
        $php = '<?php class Foo {}';

        $reflector = new ClassReflector(new StringSourceLocator($php));
        $classInfo = $reflector->reflect('Foo');

        self::assertInstanceOf(\Reflector::class, $classInfo);
    }

    public function testExportMatchesFormat() : void
    {
        self::assertStringMatchesFormat(
            \file_get_contents(__DIR__ . '/../Fixture/ExampleClassExport.txt'),
            ReflectionClass::export(ExampleClass::class)
        );
    }

    public function testToStringWhenImplementingInterface() : void
    {
        $php = '<?php
            namespace Qux;
            interface Foo {}
            class Bar implements Foo {}
        ';

        $reflection = (new ClassReflector(new StringSourceLocator($php)))->reflect('Qux\Bar');

        self::assertStringStartsWith('Class [ <user> class Qux\Bar implements Qux\Foo ] {', $reflection->__toString());
    }

    public function testToStringWhenExtending() : void
    {
        $php = '<?php
            namespace Qux;
            class Foo {}
            class Bar extends Foo {}
        ';

        $reflection = (new ClassReflector(new StringSourceLocator($php)))->reflect('Qux\Bar');

        self::assertStringStartsWith('Class [ <user> class Qux\Bar extends Qux\Foo ] {', $reflection->__toString());
    }

    public function testToStringWhenExtendingAndImplementing() : void
    {
        $php = '<?php
            namespace Qux;
            interface Foo {}
            interface Bar {}
            class Bat {}
            class Baz extends Bat implements Foo, Bar {}
        ';

        $reflection = (new ClassReflector(new StringSourceLocator($php)))->reflect('Qux\Baz');

        self::assertStringStartsWith('Class [ <user> class Qux\Baz extends Qux\Bat implements Qux\Foo, Qux\Bar ] {', $reflection->__toString());
    }

    public function testCannotClone() : void
    {
        $reflector = new ClassReflector($this->getComposerLocator());
        $classInfo = $reflector->reflect(ExampleClass::class);

        $this->expectException(Uncloneable::class);
        $unused = clone $classInfo;
    }

    public function testGetStaticPropertyValueThrowsExceptionWhenClassDoesNotExist() : void
    {
        $php = '<?php
            class Foo {}
        ';

        $classInfo = (new ClassReflector(new StringSourceLocator($php)))->reflect('Foo');
        $this->expectException(ClassDoesNotExist::class);
        $classInfo->getStaticPropertyValue('foo');
    }

    public function testGetStaticPropertyValueThrowsExceptionWhenPropertyDoesNotExist() : void
    {
        $staticPropertyGetSetFixture = __DIR__ . '/../Fixture/StaticPropertyGetSet.php';
        require_once $staticPropertyGetSetFixture;

        $classInfo = (new ClassReflector(new SingleFileSourceLocator($staticPropertyGetSetFixture)))
            ->reflect(StaticPropertyGetSet\Foo::class);

        $this->expectException(PropertyDoesNotExist::class);
        $classInfo->getStaticPropertyValue('foo');
    }

    public function testGetStaticPropertyValueThrowsExceptionWhenPropertyIsProtected() : void
    {
        $staticPropertyGetSetFixture = __DIR__ . '/../Fixture/StaticPropertyGetSet.php';
        require_once $staticPropertyGetSetFixture;

        $classInfo = (new ClassReflector(new SingleFileSourceLocator($staticPropertyGetSetFixture)))
            ->reflect(StaticPropertyGetSet\Bar::class);

        $this->expectException(PropertyNotPublic::class);
        $classInfo->getStaticPropertyValue('bat');
    }

    public function testGetStaticPropertyValueThrowsExceptionWhenPropertyIsPrivate() : void
    {
        $staticPropertyGetSetFixture = __DIR__ . '/../Fixture/StaticPropertyGetSet.php';
        require_once $staticPropertyGetSetFixture;

        $classInfo = (new ClassReflector(new SingleFileSourceLocator($staticPropertyGetSetFixture)))
            ->reflect(StaticPropertyGetSet\Bar::class);

        $this->expectException(PropertyNotPublic::class);
        $classInfo->getStaticPropertyValue('qux');
    }

    public function testGetStaticPropertyValueGetsValue() : void
    {
        $staticPropertyGetSetFixture = __DIR__ . '/../Fixture/StaticPropertyGetSet.php';
        require_once $staticPropertyGetSetFixture;

        $classInfo = (new ClassReflector(new SingleFileSourceLocator($staticPropertyGetSetFixture)))
            ->reflect(StaticPropertyGetSet\Bar::class);

        StaticPropertyGetSet\Bar::$baz = 'test value';

        self::assertSame('test value', $classInfo->getStaticPropertyValue('baz'));
    }

    public function testSetStaticPropertyValueThrowsExceptionWhenClassDoesNotExist() : void
    {
        $php = '<?php
            class Foo {}
        ';

        $classInfo = (new ClassReflector(new StringSourceLocator($php)))->reflect('Foo');
        $this->expectException(ClassDoesNotExist::class);
        $classInfo->setStaticPropertyValue('foo', 'bar');
    }

    public function testSetStaticPropertyValueThrowsExceptionWhenPropertyDoesNotExist() : void
    {
        $staticPropertyGetSetFixture = __DIR__ . '/../Fixture/StaticPropertyGetSet.php';
        require_once $staticPropertyGetSetFixture;

        $classInfo = (new ClassReflector(new SingleFileSourceLocator($staticPropertyGetSetFixture)))
            ->reflect(StaticPropertyGetSet\Foo::class);

        $this->expectException(PropertyDoesNotExist::class);
        $classInfo->setStaticPropertyValue('foo', 'bar');
    }

    public function testSetStaticPropertyValueSetsValue() : void
    {
        $staticPropertyGetSetFixture = __DIR__ . '/../Fixture/StaticPropertyGetSet.php';
        require_once $staticPropertyGetSetFixture;

        $classInfo = (new ClassReflector(new SingleFileSourceLocator($staticPropertyGetSetFixture)))
            ->reflect(StaticPropertyGetSet\Bar::class);

        StaticPropertyGetSet\Bar::$baz = 'value before';

        $classInfo->setStaticPropertyValue('baz', 'value after');

        self::assertSame('value after', StaticPropertyGetSet\Bar::$baz);
    }

    public function testGetAst() : void
    {
        $php = '<?php
            class Foo {}
        ';

        $reflection = (new ClassReflector(new StringSourceLocator($php)))->reflect('Foo');

        $ast = $reflection->getAst();

        self::assertInstanceOf(Class_::class, $ast);
        self::assertSame('Foo', $ast->name);
    }

    public function testSetIsFinal() : void
    {
        $php = '<?php
            final class Foo {}
        ';

        $reflection = (new ClassReflector(new StringSourceLocator($php)))->reflect('Foo');

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

        $reflection = (new ClassReflector(new StringSourceLocator($php)))->reflect('Foo');

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

        $reflection = (new ClassReflector(new StringSourceLocator($php)))->reflect('Foo');

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

        $reflection = (new ClassReflector(new StringSourceLocator($php)))->reflect('Foo');

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

        $reflection = (new ClassReflector(new StringSourceLocator($php)))->reflect('Foo');

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

        $reflection = (new ClassReflector(new StringSourceLocator($php)))->reflect('Foo');

        self::assertFalse($reflection->hasProperty('bar'));

        $reflection->addProperty('publicBar', \ReflectionProperty::IS_PUBLIC);
        self::assertTrue($reflection->hasProperty('publicBar'));
        self::assertTrue($reflection->getProperty('publicBar')->isPublic());

        $reflection->addProperty('protectedBar', \ReflectionProperty::IS_PROTECTED);
        self::assertTrue($reflection->hasProperty('protectedBar'));
        self::assertTrue($reflection->getProperty('protectedBar')->isProtected());

        $reflection->addProperty('privateBar', \ReflectionProperty::IS_PRIVATE);
        self::assertTrue($reflection->hasProperty('privateBar'));
        self::assertTrue($reflection->getProperty('privateBar')->isPrivate());

        $reflection->addProperty('staticBar', \ReflectionProperty::IS_PUBLIC, true);
        self::assertTrue($reflection->hasProperty('staticBar'));
        self::assertTrue($reflection->getProperty('staticBar')->isStatic());
    }

    public function testGetConstantsReturnsAllConstantsRegardlessOfVisibility()
    {
        $php = '<?php
            class Foo {
                private const BAR_PRIVATE = 1;
                protected const BAR_PROTECTED = 2;
                public const BAR_PUBLIC = 3;
                const BAR_DEFAULT = 4;
            }
        ';

        $reflection = (new ClassReflector(new StringSourceLocator($php)))->reflect('Foo');

        $expectedConstants = [
            'BAR_PRIVATE' => 1,
            'BAR_PROTECTED' => 2,
            'BAR_PUBLIC' => 3,
            'BAR_DEFAULT' => 4,
        ];

        self::assertSame($expectedConstants, $reflection->getConstants());

        \array_walk(
            $expectedConstants,
            function ($constantValue, string $constantName) use ($reflection) : void {
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
        $reflector = new ClassReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/InheritedClassConstants.php'));
        $classInfo = $reflector->reflect('Next');

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
        $reflector = new ClassReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/InheritedClassConstants.php'));
        $classInfo = $reflector->reflect('Next');

        self::assertSame(['F' => 'ff'], $classInfo->getImmediateConstants());
    }

    public function testGetReflectionConstantsReturnsInheritedConstants() : void
    {
        $reflector = new ClassReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/InheritedClassConstants.php'));
        $classInfo = $reflector->reflect('Next');

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
        self::assertSame(\array_keys($expectedConstants), \array_keys($reflectionConstants));

        \array_walk(
            $expectedConstants,
            function ($constantValue, string $constantName) use ($reflectionConstants) : void {
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
        $reflector = new ClassReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/InheritedClassConstants.php'));
        $classInfo = $reflector->reflect('Next');

        $reflectionConstants = $classInfo->getImmediateReflectionConstants();

        self::assertCount(1, $reflectionConstants);
        self::assertArrayHasKey('F', $reflectionConstants);
        self::assertInstanceOf(ReflectionClassConstant::class, $reflectionConstants['F']);
        self::assertSame('ff', $reflectionConstants['F']->getValue());
    }
}
