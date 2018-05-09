<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection;

use ClassWithPropertiesAndTraitProperties;
use ExtendedClassWithPropertiesAndTraitProperties;
use InvalidArgumentException;
use phpDocumentor\Reflection\Types;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\PropertyProperty;
use PHPUnit\Framework\TestCase;
use Reflection;
use ReflectionFunctionAbstract;
use ReflectionProperty as CoreReflectionProperty;
use Reflector;
use Roave\BetterReflection\Reflection\Exception\ClassDoesNotExist;
use Roave\BetterReflection\Reflection\Exception\NoObjectProvided;
use Roave\BetterReflection\Reflection\Exception\NotAnObject;
use Roave\BetterReflection\Reflection\Exception\ObjectNotInstanceOfClass;
use Roave\BetterReflection\Reflection\Exception\Uncloneable;
use Roave\BetterReflection\Reflection\ReflectionProperty;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\Type\ComposerSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;
use Roave\BetterReflectionTest\BetterReflectionSingleton;
use Roave\BetterReflectionTest\Fixture\ClassForHinting;
use Roave\BetterReflectionTest\Fixture\ExampleClass;
use Roave\BetterReflectionTest\Fixture\PropertyGetSet;
use Roave\BetterReflectionTest\Fixture\StaticPropertyGetSet;
use stdClass;
use Throwable;
use TraitWithProperty;
use function count;

/**
 * @covers \Roave\BetterReflection\Reflection\ReflectionProperty
 */
class ReflectionPropertyTest extends TestCase
{
    /** @var ClassReflector */
    private $reflector;

    /** @var Locator */
    private $astLocator;

    public function setUp() : void
    {
        parent::setUp();

        $this->astLocator = BetterReflectionSingleton::instance()->astLocator();
        $this->reflector  = new ClassReflector(new ComposerSourceLocator($GLOBALS['loader'], $this->astLocator));
    }

    public function testCreateFromName() : void
    {
        $property = ReflectionProperty::createFromName(ReflectionFunctionAbstract::class, 'name');

        self::assertInstanceOf(ReflectionProperty::class, $property);
        self::assertSame('name', $property->getName());
    }

    public function testCreateFromInstance() : void
    {
        $property = ReflectionProperty::createFromInstance(new ClassForHinting(), 'someProperty');

        self::assertInstanceOf(ReflectionProperty::class, $property);
        self::assertSame('someProperty', $property->getName());
    }

    public function testImplementsReflector() : void
    {
        $classInfo  = $this->reflector->reflect(ExampleClass::class);
        $publicProp = $classInfo->getProperty('publicProperty');

        self::assertInstanceOf(Reflector::class, $publicProp);
    }

    public function testVisibilityMethods() : void
    {
        $classInfo = $this->reflector->reflect(ExampleClass::class);

        $privateProp = $classInfo->getProperty('privateProperty');
        self::assertTrue($privateProp->isPrivate());

        $protectedProp = $classInfo->getProperty('protectedProperty');
        self::assertTrue($protectedProp->isProtected());

        $publicProp = $classInfo->getProperty('publicProperty');
        self::assertTrue($publicProp->isPublic());
    }

    public function testIsStatic() : void
    {
        $classInfo = $this->reflector->reflect(ExampleClass::class);

        $publicProp = $classInfo->getProperty('publicProperty');
        self::assertFalse($publicProp->isStatic());

        $staticProp = $classInfo->getProperty('publicStaticProperty');
        self::assertTrue($staticProp->isStatic());
    }

    /**
     * @return array
     */
    public function stringTypesDataProvider() : array
    {
        return [
            ['privateProperty', ['int', 'float', '\stdClass']],
            ['protectedProperty', ['bool', 'bool[]', 'bool[][]']],
            ['publicProperty', ['string']],
        ];
    }

    /**
     * @param string[] $expectedTypes
     * @dataProvider stringTypesDataProvider
     */
    public function testGetDocBlockTypeStrings(string $propertyName, array $expectedTypes) : void
    {
        $classInfo = $this->reflector->reflect(ExampleClass::class);

        $property = $classInfo->getProperty($propertyName);

        self::assertSame($expectedTypes, $property->getDocBlockTypeStrings());
    }

    /**
     * @return array
     */
    public function typesDataProvider() : array
    {
        return [
            ['privateProperty', [Types\Integer::class, Types\Float_::class, Types\Object_::class]],
            ['protectedProperty', [Types\Boolean::class, Types\Array_::class, Types\Array_::class]],
            ['publicProperty', [Types\String_::class]],
        ];
    }

    /**
     * @param string[] $expectedTypes
     * @dataProvider typesDataProvider
     */
    public function testGetDocBlockTypes(string $propertyName, array $expectedTypes) : void
    {
        $classInfo = $this->reflector->reflect(ExampleClass::class);

        $foundTypes = $classInfo->getProperty($propertyName)->getDocBlockTypes();

        self::assertCount(count($expectedTypes), $foundTypes);

        foreach ($expectedTypes as $i => $expectedType) {
            self::assertInstanceOf($expectedType, $foundTypes[$i]);
        }
    }

    public function testGetDocComment() : void
    {
        $expectedDoc = "/**\n * @var string\n */";

        $classInfo = $this->reflector->reflect(ExampleClass::class);
        $property  = $classInfo->getProperty('publicProperty');

        self::assertSame($expectedDoc, $property->getDocComment());
    }

    public function testGetDocCommentBetweeenComments() : void
    {
        $php       = '<?php
            class Bar implements Foo {
                /* A comment  */
                /** Property description */
                /* An another comment */
                public $prop;
            }
        ';
        $reflector = (new ClassReflector(new StringSourceLocator($php, $this->astLocator)))->reflect('Bar');
        $property  = $reflector->getProperty('prop');

        self::assertContains('Property description', $property->getDocComment());
    }

    public function testGetDocCommentReturnsEmptyStringWithNoComment() : void
    {
        $classInfo = $this->reflector->reflect(ExampleClass::class);
        $property  = $classInfo->getProperty('publicStaticProperty');

        self::assertSame('', $property->getDocComment());
    }

    public function testExportThrowsException() : void
    {
        $this->expectException(Throwable::class);
        ReflectionProperty::export();
    }

    public function modifierProvider() : array
    {
        return [
            ['publicProperty', CoreReflectionProperty::IS_PUBLIC, ['public']],
            ['protectedProperty', CoreReflectionProperty::IS_PROTECTED, ['protected']],
            ['privateProperty', CoreReflectionProperty::IS_PRIVATE, ['private']],
            ['publicStaticProperty', CoreReflectionProperty::IS_PUBLIC | CoreReflectionProperty::IS_STATIC, ['public', 'static']],
        ];
    }

    /**
     * @param string[] $expectedModifierNames
     * @dataProvider modifierProvider
     */
    public function testGetModifiers(string $propertyName, int $expectedModifier, array $expectedModifierNames) : void
    {
        $classInfo = $this->reflector->reflect(ExampleClass::class);
        $property  = $classInfo->getProperty($propertyName);

        self::assertSame($expectedModifier, $property->getModifiers());
        self::assertSame(
            $expectedModifierNames,
            Reflection::getModifierNames($property->getModifiers())
        );
    }

    public function testIsDefault() : void
    {
        $classInfo = $this->reflector->reflect(ExampleClass::class);

        self::assertTrue($classInfo->getProperty('publicProperty')->isDefault());
        self::assertTrue($classInfo->getProperty('publicStaticProperty')->isDefault());
    }

    public function testIsDefaultWithRuntimeDeclaredProperty() : void
    {
        $classInfo = $this->reflector->reflect(ExampleClass::class);

        self::assertFalse(
            ReflectionProperty::createFromNode(
                $this->reflector,
                new Property(Class_::MODIFIER_PUBLIC, [new PropertyProperty('foo')]),
                0,
                null,
                $classInfo,
                $classInfo,
                false
            )
            ->isDefault()
        );
    }

    public function testToString() : void
    {
        $classInfo = $this->reflector->reflect(ExampleClass::class);
        self::assertSame('Property [ <default> public $publicProperty ]', (string) $classInfo->getProperty('publicProperty'));
    }

    public function testGetDefaultProperty() : void
    {
        $classInfo = (new ClassReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/DefaultProperties.php', $this->astLocator)))->reflect('Foo');

        self::assertSame(123, $classInfo->getProperty('hasDefault')->getDefaultValue());
        self::assertNull($classInfo->getProperty('noDefault')->getDefaultValue());
    }

    public function testCannotClone() : void
    {
        $classInfo  = $this->reflector->reflect(ExampleClass::class);
        $publicProp = $classInfo->getProperty('publicProperty');

        $this->expectException(Uncloneable::class);
        $unused = clone $publicProp;
    }

    public function testSetVisibility() : void
    {
        $classInfo  = $this->reflector->reflect(ExampleClass::class);
        $publicProp = $classInfo->getProperty('publicStaticProperty');

        self::assertFalse($publicProp->isPrivate(), 'Should initially be public, was private');
        self::assertFalse($publicProp->isProtected(), 'Should initially be public, was protected');
        self::assertTrue($publicProp->isPublic(), 'Should initially be public, was not public');
        self::assertTrue($publicProp->isStatic(), 'Should initially be static');

        $publicProp->setVisibility(CoreReflectionProperty::IS_PRIVATE);

        self::assertTrue($publicProp->isPrivate(), 'After setting private, isPrivate is not set');
        self::assertFalse($publicProp->isProtected(), 'After setting private, protected is now set but should not be');
        self::assertFalse($publicProp->isPublic(), 'After setting private, public is still set but should not be');
        self::assertTrue($publicProp->isStatic(), 'Should still be static after setting private');

        $publicProp->setVisibility(CoreReflectionProperty::IS_PROTECTED);

        self::assertFalse($publicProp->isPrivate(), 'After setting protected, should no longer be private');
        self::assertTrue($publicProp->isProtected(), 'After setting protected, expect isProtected to be set');
        self::assertFalse($publicProp->isPublic(), 'After setting protected, public is set but should not be');
        self::assertTrue($publicProp->isStatic(), 'Should still be static after setting protected');

        $publicProp->setVisibility(CoreReflectionProperty::IS_PUBLIC);

        self::assertFalse($publicProp->isPrivate(), 'After setting public, isPrivate should not be set');
        self::assertFalse($publicProp->isProtected(), 'After setting public, isProtected should not be set');
        self::assertTrue($publicProp->isPublic(), 'After setting public, isPublic should be set but was not');
        self::assertTrue($publicProp->isStatic(), 'Should still be static after setting public');
    }

    public function testSetVisibilityThrowsExceptionWithInvalidArgument() : void
    {
        $classInfo  = $this->reflector->reflect(ExampleClass::class);
        $publicProp = $classInfo->getProperty('publicProperty');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Visibility should be \ReflectionProperty::IS_PRIVATE, ::IS_PROTECTED or ::IS_PUBLIC constants');
        $publicProp->setVisibility(0);
    }

    /**
     * @dataProvider startEndLineProvider
     */
    public function testStartEndLine(string $php, int $startLine, int $endLine) : void
    {
        $reflector       = new ClassReflector(new StringSourceLocator($php, $this->astLocator));
        $classReflection = $reflector->reflect('\T');
        $constReflection = $classReflection->getProperty('test');
        $this->assertEquals($startLine, $constReflection->getStartLine());
        $this->assertEquals($endLine, $constReflection->getEndLine());
    }

    public function startEndLineProvider() : array
    {
        return [
            ["<?php\nclass T {\npublic \$test = 1; }", 3, 3],
            ["<?php\n\nclass T {\npublic \$test = 1; }", 4, 4],
            ["<?php\nclass T {\npublic \$test = \n1; }", 3, 4],
            ["<?php\nclass T {\npublic \n\$test = 1; }", 3, 4],
        ];
    }

    public function columsProvider() : array
    {
        return [
            ["<?php\n\nclass T {\npublic \$test = 1;\n}", 1, 17],
            ["<?php\n\n    class T {\n        protected \$test = 1;\n    }", 9, 28],
            ['<?php class T {private $test = 1;}', 16, 33],
        ];
    }

    /**
     * @dataProvider columsProvider
     */
    public function testGetStartColumnAndEndColumn(string $php, int $startColumn, int $endColumn) : void
    {
        $reflector          = new ClassReflector(new StringSourceLocator($php, $this->astLocator));
        $classReflection    = $reflector->reflect('T');
        $constantReflection = $classReflection->getProperty('test');

        self::assertEquals($startColumn, $constantReflection->getStartColumn());
        self::assertEquals($endColumn, $constantReflection->getEndColumn());
    }

    public function getAstProvider() : array
    {
        return [
            ['a', 0],
            ['b', 1],
            ['c', 0],
            ['d', 1],
        ];
    }

    /**
     * @dataProvider getAstProvider
     */
    public function testGetAst(string $propertyName, int $positionInAst) : void
    {
        $php = <<<'PHP'
<?php
class Foo
{
    private $a = 0,
            $b = 1;
    protected $c = 3,
              $d = 4;         
}
PHP;

        $classReflection    = (new ClassReflector(new StringSourceLocator($php, $this->astLocator)))->reflect('Foo');
        $propertyReflection = $classReflection->getProperty($propertyName);

        $ast = $propertyReflection->getAst();

        self::assertInstanceOf(Property::class, $ast);
        self::assertSame($positionInAst, $propertyReflection->getPositionInAst());
        self::assertSame($propertyName, $ast->props[$positionInAst]->name->name);
    }

    public function testGetDeclaringAndImplementingClassWithPropertyFromTrait() : void
    {
        $classReflector     = new ClassReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/ClassWithPropertiesAndTraitProperties.php', $this->astLocator));
        $classReflection    = $classReflector->reflect(ClassWithPropertiesAndTraitProperties::class);
        $propertyReflection = $classReflection->getProperty('propertyFromTrait');

        self::assertSame(TraitWithProperty::class, $propertyReflection->getDeclaringClass()->getName());
        self::assertSame(ClassWithPropertiesAndTraitProperties::class, $propertyReflection->getImplementingClass()->getName());
        self::assertNotSame($propertyReflection->getDeclaringClass(), $propertyReflection->getImplementingClass());
    }

    public function testGetDeclaringAndImplementingClassWithPropertyFromClass() : void
    {
        $classReflector     = new ClassReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/ClassWithPropertiesAndTraitProperties.php', $this->astLocator));
        $classReflection    = $classReflector->reflect(ClassWithPropertiesAndTraitProperties::class);
        $propertyReflection = $classReflection->getProperty('propertyFromClass');

        self::assertSame(ClassWithPropertiesAndTraitProperties::class, $propertyReflection->getDeclaringClass()->getName());
        self::assertSame(ClassWithPropertiesAndTraitProperties::class, $propertyReflection->getImplementingClass()->getName());
        self::assertSame($propertyReflection->getDeclaringClass(), $propertyReflection->getImplementingClass());
    }

    public function testGetDeclaringAndImplementingClassWithPropertyFromParentClass() : void
    {
        $classReflector     = new ClassReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/ClassWithPropertiesAndTraitProperties.php', $this->astLocator));
        $classReflection    = $classReflector->reflect(ExtendedClassWithPropertiesAndTraitProperties::class)->getParentClass();
        $propertyReflection = $classReflection->getProperty('propertyFromClass');

        self::assertSame(ClassWithPropertiesAndTraitProperties::class, $propertyReflection->getDeclaringClass()->getName());
        self::assertSame(ClassWithPropertiesAndTraitProperties::class, $propertyReflection->getImplementingClass()->getName());
        self::assertSame($propertyReflection->getDeclaringClass(), $propertyReflection->getImplementingClass());
    }

    public function testSetAndGetValueOfStaticProperty() : void
    {
        $staticPropertyGetSetFixture = __DIR__ . '/../Fixture/StaticPropertyGetSet.php';
        require_once $staticPropertyGetSetFixture;

        $classReflection    = (new ClassReflector(new SingleFileSourceLocator($staticPropertyGetSetFixture, $this->astLocator)))->reflect(StaticPropertyGetSet::class);
        $propertyReflection = $classReflection->getProperty('baz');

        $propertyReflection->setValue('value');

        self::assertSame('value', StaticPropertyGetSet::$baz);
        self::assertSame('value', $propertyReflection->getValue());
    }

    public function testSetValueOfStaticPropertyWithValueAsSecondParameter() : void
    {
        $staticPropertyGetSetFixture = __DIR__ . '/../Fixture/StaticPropertyGetSet.php';
        require_once $staticPropertyGetSetFixture;

        $classReflection    = (new ClassReflector(new SingleFileSourceLocator($staticPropertyGetSetFixture, $this->astLocator)))->reflect(StaticPropertyGetSet::class);
        $propertyReflection = $classReflection->getProperty('baz');

        $propertyReflection->setValue('first', 'second');

        self::assertSame('second', StaticPropertyGetSet::$baz);
        self::assertSame('second', $propertyReflection->getValue());
    }

    public function testSetValueOfStaticPropertyThrowsExceptionWhenClassDoesNotExist() : void
    {
        $php = <<<'PHP'
<?php
class Foo
{
    public static $boo = 'boo';
}
PHP;

        $this->expectException(ClassDoesNotExist::class);

        $classReflection    = (new ClassReflector(new StringSourceLocator($php, $this->astLocator)))->reflect('Foo');
        $propertyReflection = $classReflection->getProperty('boo');

        $propertyReflection->setValue(null);
    }

    public function testGetValueOfStaticPropertyThrowsExceptionWhenClassDoesNotExist() : void
    {
        $php = <<<'PHP'
<?php
class Foo
{
    public static $boo = 'boo';
}
PHP;

        $this->expectException(ClassDoesNotExist::class);

        $classReflection    = (new ClassReflector(new StringSourceLocator($php, $this->astLocator)))->reflect('Foo');
        $propertyReflection = $classReflection->getProperty('boo');

        $propertyReflection->getValue();
    }

    public function testSetAccessibleAndSetAndGetValueOfStaticProperty() : void
    {
        $staticPropertyGetSetFixtureFile = __DIR__ . '/../Fixture/StaticPropertyGetSet.php';
        require_once $staticPropertyGetSetFixtureFile;

        $classReflection    = (new ClassReflector(new SingleFileSourceLocator($staticPropertyGetSetFixtureFile, $this->astLocator)))->reflect(StaticPropertyGetSet::class);
        $propertyReflection = $classReflection->getProperty('bat');

        $object = new PropertyGetSet();

        $propertyReflection->setValue($object, 'batman');

        self::assertSame('batman', $propertyReflection->getValue($object));
    }

    public function testSetAndGetValueOfObjectProperty() : void
    {
        $propertyGetSetFixture = __DIR__ . '/../Fixture/PropertyGetSet.php';
        require_once $propertyGetSetFixture;

        $classReflection    = (new ClassReflector(new SingleFileSourceLocator($propertyGetSetFixture, $this->astLocator)))->reflect(PropertyGetSet::class);
        $propertyReflection = $classReflection->getProperty('baz');

        $object = new PropertyGetSet();

        $propertyReflection->setValue($object, 'value');

        self::assertSame('value', $object->baz);
        self::assertSame('value', $propertyReflection->getValue($object));
    }

    public function testSetValueOfObjectPropertyThrowsExceptionWhenNoObject() : void
    {
        $this->expectException(NoObjectProvided::class);

        $classReflection    = (new ClassReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/PropertyGetSet.php', $this->astLocator)))->reflect(PropertyGetSet::class);
        $propertyReflection = $classReflection->getProperty('baz');

        $propertyReflection->setValue(null);
    }

    public function testGetValueOfObjectPropertyThrowsExceptionWhenNoObject() : void
    {
        $this->expectException(NoObjectProvided::class);

        $classReflection    = (new ClassReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/PropertyGetSet.php', $this->astLocator)))->reflect(PropertyGetSet::class);
        $propertyReflection = $classReflection->getProperty('baz');

        $propertyReflection->getValue();
    }

    public function testSetValueOfObjectPropertyThrowsExceptionWhenNotAnObject() : void
    {
        $this->expectException(NotAnObject::class);

        $classReflection    = (new ClassReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/PropertyGetSet.php', $this->astLocator)))->reflect(PropertyGetSet::class);
        $propertyReflection = $classReflection->getProperty('baz');

        $propertyReflection->setValue('string');
    }

    public function testGetValueOfObjectPropertyThrowsExceptionNotAnObject() : void
    {
        $this->expectException(NotAnObject::class);

        $classReflection    = (new ClassReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/PropertyGetSet.php', $this->astLocator)))->reflect(PropertyGetSet::class);
        $propertyReflection = $classReflection->getProperty('baz');

        $propertyReflection->getValue('string');
    }

    public function testSetValueOfObjectPropertyThrowsExceptionWhenObjectNotInstanceOfClass() : void
    {
        $this->expectException(ObjectNotInstanceOfClass::class);

        $classReflection    = (new ClassReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/PropertyGetSet.php', $this->astLocator)))->reflect(PropertyGetSet::class);
        $propertyReflection = $classReflection->getProperty('baz');

        $propertyReflection->setValue(new stdClass());
    }

    public function testGetValueOfObjectPropertyThrowsExceptionObjectNotInstanceOfClass() : void
    {
        $this->expectException(ObjectNotInstanceOfClass::class);

        $classReflection    = (new ClassReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/PropertyGetSet.php', $this->astLocator)))->reflect(PropertyGetSet::class);
        $propertyReflection = $classReflection->getProperty('baz');

        $propertyReflection->getValue(new stdClass());
    }

    public function testSetAccessibleAndSetAndGetValueOfObjectProperty() : void
    {
        $propertyGetSetFixtureFile = __DIR__ . '/../Fixture/PropertyGetSet.php';
        require_once $propertyGetSetFixtureFile;

        $classReflection    = (new ClassReflector(new SingleFileSourceLocator($propertyGetSetFixtureFile, $this->astLocator)))->reflect(PropertyGetSet::class);
        $propertyReflection = $classReflection->getProperty('bat');

        $object = new PropertyGetSet();

        $propertyReflection->setValue($object, 'batman');

        self::assertSame('batman', $propertyReflection->getValue($object));
    }
}
