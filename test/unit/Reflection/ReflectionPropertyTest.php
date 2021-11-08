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
use PhpParser\PrettyPrinter\Standard as StandardPrettyPrinter;
use PHPUnit\Framework\TestCase;
use Reflection;
use ReflectionProperty as CoreReflectionProperty;
use Roave\BetterReflection\Reflection\Exception\ClassDoesNotExist;
use Roave\BetterReflection\Reflection\Exception\NoObjectProvided;
use Roave\BetterReflection\Reflection\Exception\NotAnObject;
use Roave\BetterReflection\Reflection\Exception\ObjectNotInstanceOfClass;
use Roave\BetterReflection\Reflection\Exception\Uncloneable;
use Roave\BetterReflection\Reflection\ReflectionProperty;
use Roave\BetterReflection\Reflector\DefaultReflector;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\Type\ComposerSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;
use Roave\BetterReflectionTest\BetterReflectionSingleton;
use Roave\BetterReflectionTest\Fixture\Attr;
use Roave\BetterReflectionTest\Fixture\ClassForHinting;
use Roave\BetterReflectionTest\Fixture\ClassUsesTraitStaticPropertyGetSet;
use Roave\BetterReflectionTest\Fixture\ClassWithAttributes;
use Roave\BetterReflectionTest\Fixture\ExampleClass;
use Roave\BetterReflectionTest\Fixture\InitializedProperties;
use Roave\BetterReflectionTest\Fixture\Php74PropertyTypeDeclarations;
use Roave\BetterReflectionTest\Fixture\PropertyGetSet;
use Roave\BetterReflectionTest\Fixture\StaticPropertyGetSet;
use Roave\BetterReflectionTest\Fixture\TraitStaticPropertyGetSet;
use stdClass;
use TraitWithProperty;

use function count;
use function sprintf;

/**
 * @covers \Roave\BetterReflection\Reflection\ReflectionProperty
 */
class ReflectionPropertyTest extends TestCase
{
    private Reflector $reflector;

    private Locator $astLocator;

    public function setUp(): void
    {
        parent::setUp();

        $this->astLocator = BetterReflectionSingleton::instance()->astLocator();
        $this->reflector  = new DefaultReflector(new ComposerSourceLocator($GLOBALS['loader'], $this->astLocator));
    }

    public function testCreateFromName(): void
    {
        $property = ReflectionProperty::createFromName(ReflectionProperty::class, 'node');

        self::assertInstanceOf(ReflectionProperty::class, $property);
        self::assertSame('node', $property->getName());
    }

    public function testCreateFromInstance(): void
    {
        $property = ReflectionProperty::createFromInstance(new ClassForHinting(), 'someProperty');

        self::assertInstanceOf(ReflectionProperty::class, $property);
        self::assertSame('someProperty', $property->getName());
    }

    public function testVisibilityMethods(): void
    {
        $classInfo = $this->reflector->reflectClass(ExampleClass::class);

        $privateProp = $classInfo->getProperty('privateProperty');
        self::assertTrue($privateProp->isPrivate());

        $protectedProp = $classInfo->getProperty('protectedProperty');
        self::assertTrue($protectedProp->isProtected());

        $publicProp = $classInfo->getProperty('publicProperty');
        self::assertTrue($publicProp->isPublic());
    }

    public function testIsStatic(): void
    {
        $classInfo = $this->reflector->reflectClass(ExampleClass::class);

        $publicProp = $classInfo->getProperty('publicProperty');
        self::assertFalse($publicProp->isStatic());

        $staticProp = $classInfo->getProperty('publicStaticProperty');
        self::assertTrue($staticProp->isStatic());
    }

    public function testIsReadOnly(): void
    {
        $classInfo = $this->reflector->reflectClass(ExampleClass::class);

        $notReadOnlyProperty = $classInfo->getProperty('publicProperty');
        self::assertFalse($notReadOnlyProperty->isReadOnly());

        $readOnlyProperty = $classInfo->getProperty('readOnlyProperty');
        self::assertTrue($readOnlyProperty->isReadOnly());
    }

    /**
     * @return array
     */
    public function stringTypesDataProvider(): array
    {
        return [
            ['privateProperty', ['int', 'float', '\stdClass']],
            ['protectedProperty', ['bool', 'bool[]', 'bool[][]']],
            ['publicProperty', ['string']],
        ];
    }

    /**
     * @param list<string> $expectedTypes
     *
     * @dataProvider stringTypesDataProvider
     */
    public function testGetDocBlockTypeStrings(string $propertyName, array $expectedTypes): void
    {
        $classInfo = $this->reflector->reflectClass(ExampleClass::class);

        $property = $classInfo->getProperty($propertyName);

        self::assertSame($expectedTypes, $property->getDocBlockTypeStrings());
    }

    /**
     * @return array
     */
    public function typesDataProvider(): array
    {
        return [
            ['privateProperty', [Types\Integer::class, Types\Float_::class, Types\Object_::class]],
            ['protectedProperty', [Types\Boolean::class, Types\Array_::class, Types\Array_::class]],
            ['publicProperty', [Types\String_::class]],
        ];
    }

    /**
     * @param list<string> $expectedTypes
     *
     * @dataProvider typesDataProvider
     */
    public function testGetDocBlockTypes(string $propertyName, array $expectedTypes): void
    {
        $classInfo = $this->reflector->reflectClass(ExampleClass::class);

        $foundTypes = $classInfo->getProperty($propertyName)->getDocBlockTypes();

        self::assertCount(count($expectedTypes), $foundTypes);

        foreach ($expectedTypes as $i => $expectedType) {
            self::assertInstanceOf($expectedType, $foundTypes[$i]);
        }
    }

    public function testGetDocComment(): void
    {
        $expectedDoc = "/**\n * @var string\n */";

        $classInfo = $this->reflector->reflectClass(ExampleClass::class);
        $property  = $classInfo->getProperty('publicProperty');

        self::assertSame($expectedDoc, $property->getDocComment());
    }

    public function testGetDocCommentBetweenComments(): void
    {
        $php       = '<?php
            class Bar implements Foo {
                /* A comment  */
                /** Property description */
                /* An another comment */
                public $prop;
            }
        ';
        $reflector = (new DefaultReflector(new StringSourceLocator($php, $this->astLocator)))->reflectClass('Bar');
        $property  = $reflector->getProperty('prop');

        self::assertStringContainsString('Property description', $property->getDocComment());
    }

    public function testGetDocCommentReturnsEmptyStringWithNoComment(): void
    {
        $classInfo = $this->reflector->reflectClass(ExampleClass::class);
        $property  = $classInfo->getProperty('publicStaticProperty');

        self::assertSame('', $property->getDocComment());
    }

    public function modifierProvider(): array
    {
        return [
            ['publicProperty', CoreReflectionProperty::IS_PUBLIC, ['public']],
            ['protectedProperty', CoreReflectionProperty::IS_PROTECTED, ['protected']],
            ['privateProperty', CoreReflectionProperty::IS_PRIVATE, ['private']],
            ['publicStaticProperty', CoreReflectionProperty::IS_PUBLIC | CoreReflectionProperty::IS_STATIC, ['public', 'static']],
        ];
    }

    /**
     * @param list<string> $expectedModifierNames
     *
     * @dataProvider modifierProvider
     */
    public function testGetModifiers(string $propertyName, int $expectedModifier, array $expectedModifierNames): void
    {
        $classInfo = $this->reflector->reflectClass(ExampleClass::class);
        $property  = $classInfo->getProperty($propertyName);

        self::assertSame($expectedModifier, $property->getModifiers());
        self::assertSame(
            $expectedModifierNames,
            Reflection::getModifierNames($property->getModifiers()),
        );
    }

    public function testIsPromoted(): void
    {
        $classInfo = $this->reflector->reflectClass(ExampleClass::class);

        $promotedProperty = $classInfo->getProperty('promotedProperty');

        self::assertTrue($promotedProperty->isPromoted());
        self::assertTrue($promotedProperty->isPrivate());
        self::assertTrue($promotedProperty->hasType());
        self::assertSame('?int', $promotedProperty->getType()->__toString());
        self::assertTrue($promotedProperty->hasDefaultValue());
        self:self::assertSame(123, $promotedProperty->getDefaultValue());
    }

    public function testIsDefault(): void
    {
        $classInfo = $this->reflector->reflectClass(ExampleClass::class);

        self::assertTrue($classInfo->getProperty('publicProperty')->isDefault());
        self::assertTrue($classInfo->getProperty('publicStaticProperty')->isDefault());
    }

    public function testIsDefaultWithRuntimeDeclaredProperty(): void
    {
        $classInfo = $this->reflector->reflectClass(ExampleClass::class);

        self::assertFalse(
            ReflectionProperty::createFromNode(
                $this->reflector,
                new Property(Class_::MODIFIER_PUBLIC, [new PropertyProperty('foo')]),
                0,
                null,
                $classInfo,
                $classInfo,
                false,
                false,
            )
            ->isDefault(),
        );
    }

    public function testToString(): void
    {
        $classInfo = $this->reflector->reflectClass(ExampleClass::class);
        self::assertSame('Property [ <default> public $publicProperty ]', (string) $classInfo->getProperty('publicProperty'));
    }

    public function propertyDefaultValueProvider(): array
    {
        return [
            ['hasDefault', true, 123],
            ['hasNullAsDefault', true, null],
            ['noDefault', true, null],
            ['hasDefaultWithType', true, 123],
            ['hasNullAsDefaultWithType', true, null],
            ['noDefaultWithType', false, null],
            ['fromTrait', true, 'const'],
        ];
    }

    /**
     * @dataProvider propertyDefaultValueProvider
     */
    public function testPropertyDefaultValue(string $propertyName, bool $hasDefaultValue, mixed $defaultValue): void
    {
        $classInfo = (new DefaultReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/DefaultProperties.php', $this->astLocator)))->reflectClass('Foo');
        $property  = $classInfo->getProperty($propertyName);

        self::assertSame($hasDefaultValue, $property->hasDefaultValue());
        self::assertSame($defaultValue, $property->getDefaultValue());
    }

    public function testCannotClone(): void
    {
        $classInfo  = $this->reflector->reflectClass(ExampleClass::class);
        $publicProp = $classInfo->getProperty('publicProperty');

        $this->expectException(Uncloneable::class);
        clone $publicProp;
    }

    public function testSetVisibility(): void
    {
        $classInfo  = $this->reflector->reflectClass(ExampleClass::class);
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

    public function testSetVisibilityThrowsExceptionWithInvalidArgument(): void
    {
        $classInfo  = $this->reflector->reflectClass(ExampleClass::class);
        $publicProp = $classInfo->getProperty('publicProperty');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Visibility should be \ReflectionProperty::IS_PRIVATE, ::IS_PROTECTED or ::IS_PUBLIC constants');
        $publicProp->setVisibility(0);
    }

    /**
     * @dataProvider startEndLineProvider
     */
    public function testStartEndLine(string $php, int $startLine, int $endLine): void
    {
        $reflector       = new DefaultReflector(new StringSourceLocator($php, $this->astLocator));
        $classReflection = $reflector->reflectClass('\T');
        $constReflection = $classReflection->getProperty('test');
        self::assertEquals($startLine, $constReflection->getStartLine());
        self::assertEquals($endLine, $constReflection->getEndLine());
    }

    public function startEndLineProvider(): array
    {
        return [
            ["<?php\nclass T {\npublic \$test = 1; }", 3, 3],
            ["<?php\n\nclass T {\npublic \$test = 1; }", 4, 4],
            ["<?php\nclass T {\npublic \$test = \n1; }", 3, 4],
            ["<?php\nclass T {\npublic \n\$test = 1; }", 3, 4],
        ];
    }

    public function columnsProvider(): array
    {
        return [
            ["<?php\n\nclass T {\npublic \$test = 1;\n}", 1, 17],
            ["<?php\n\n    class T {\n        protected \$test = 1;\n    }", 9, 28],
            ['<?php class T {private $test = 1;}', 16, 33],
        ];
    }

    /**
     * @dataProvider columnsProvider
     */
    public function testGetStartColumnAndEndColumn(string $php, int $startColumn, int $endColumn): void
    {
        $reflector          = new DefaultReflector(new StringSourceLocator($php, $this->astLocator));
        $classReflection    = $reflector->reflectClass('T');
        $constantReflection = $classReflection->getProperty('test');

        self::assertEquals($startColumn, $constantReflection->getStartColumn());
        self::assertEquals($endColumn, $constantReflection->getEndColumn());
    }

    public function getAstProvider(): array
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
    public function testGetAst(string $propertyName, int $positionInAst): void
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

        $classReflection    = (new DefaultReflector(new StringSourceLocator($php, $this->astLocator)))->reflectClass('Foo');
        $propertyReflection = $classReflection->getProperty($propertyName);

        $ast = $propertyReflection->getAst();

        self::assertInstanceOf(Property::class, $ast);
        self::assertSame($positionInAst, $propertyReflection->getPositionInAst());
        self::assertSame($propertyName, $ast->props[$positionInAst]->name->name);
    }

    public function testGetDeclaringAndImplementingClassWithPropertyFromTrait(): void
    {
        $reflector          = new DefaultReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/ClassWithPropertiesAndTraitProperties.php', $this->astLocator));
        $classReflection    = $reflector->reflectClass(ClassWithPropertiesAndTraitProperties::class);
        $propertyReflection = $classReflection->getProperty('propertyFromTrait');

        self::assertSame(TraitWithProperty::class, $propertyReflection->getDeclaringClass()->getName());
        self::assertSame(ClassWithPropertiesAndTraitProperties::class, $propertyReflection->getImplementingClass()->getName());
        self::assertNotSame($propertyReflection->getDeclaringClass(), $propertyReflection->getImplementingClass());
    }

    public function testGetDeclaringAndImplementingClassWithPropertyFromClass(): void
    {
        $reflector          = new DefaultReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/ClassWithPropertiesAndTraitProperties.php', $this->astLocator));
        $classReflection    = $reflector->reflectClass(ClassWithPropertiesAndTraitProperties::class);
        $propertyReflection = $classReflection->getProperty('propertyFromClass');

        self::assertSame(ClassWithPropertiesAndTraitProperties::class, $propertyReflection->getDeclaringClass()->getName());
        self::assertSame(ClassWithPropertiesAndTraitProperties::class, $propertyReflection->getImplementingClass()->getName());
        self::assertSame($propertyReflection->getDeclaringClass(), $propertyReflection->getImplementingClass());
    }

    public function testGetDeclaringAndImplementingClassWithPropertyFromParentClass(): void
    {
        $reflector          = new DefaultReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/ClassWithPropertiesAndTraitProperties.php', $this->astLocator));
        $classReflection    = $reflector->reflectClass(ExtendedClassWithPropertiesAndTraitProperties::class)->getParentClass();
        $propertyReflection = $classReflection->getProperty('propertyFromClass');

        self::assertSame(ClassWithPropertiesAndTraitProperties::class, $propertyReflection->getDeclaringClass()->getName());
        self::assertSame(ClassWithPropertiesAndTraitProperties::class, $propertyReflection->getImplementingClass()->getName());
        self::assertSame($propertyReflection->getDeclaringClass(), $propertyReflection->getImplementingClass());
    }

    /**
     * @runInSeparateProcess
     */
    public function testSetAndGetValueOfStaticProperty(): void
    {
        $staticPropertyGetSetFixture = __DIR__ . '/../Fixture/StaticPropertyGetSet.php';
        require_once $staticPropertyGetSetFixture;

        $classReflection    = (new DefaultReflector(new SingleFileSourceLocator($staticPropertyGetSetFixture, $this->astLocator)))->reflectClass(StaticPropertyGetSet::class);
        $propertyReflection = $classReflection->getProperty('baz');

        $propertyReflection->setValue('value');

        self::assertSame('value', StaticPropertyGetSet::$baz);
        self::assertSame('value', $propertyReflection->getValue());
    }

    /**
     * Accessing static trait property is deprecated in PHP 8.1, it should only be accessed on a class using the trait
     *
     * @requires PHP < 8.1
     */
    public function testSetAndGetValueOfStaticPropertyOnTrait(): void
    {
        $staticPropertyGetSetFixture = __DIR__ . '/../Fixture/TraitStaticPropertyGetSet.php';
        require_once $staticPropertyGetSetFixture;

        $classReflection    = (new DefaultReflector(new SingleFileSourceLocator($staticPropertyGetSetFixture, $this->astLocator)))->reflectClass(TraitStaticPropertyGetSet::class);
        $propertyReflection = $classReflection->getProperty('staticProperty');

        $propertyReflection->setValue('value');

        self::assertSame('value', TraitStaticPropertyGetSet::$staticProperty);
        self::assertSame('value', $propertyReflection->getValue());
    }

    public function testSetAndGetValueOfStaticPropertyOnClassUsingTrait(): void
    {
        $staticPropertyGetSetFixture = __DIR__ . '/../Fixture/ClassUsesTraitStaticPropertyGetSet.php';
        require_once $staticPropertyGetSetFixture;

        $classReflection    = (new DefaultReflector(new SingleFileSourceLocator($staticPropertyGetSetFixture, $this->astLocator)))->reflectClass(ClassUsesTraitStaticPropertyGetSet::class);
        $propertyReflection = $classReflection->getProperty('staticProperty');

        $propertyReflection->setValue('value');

        self::assertSame('value', ClassUsesTraitStaticPropertyGetSet::$staticProperty);
        self::assertSame('value', $propertyReflection->getValue());
    }

    /**
     * @runInSeparateProcess
     */
    public function testSetValueOfStaticPropertyWithValueAsSecondParameter(): void
    {
        $staticPropertyGetSetFixture = __DIR__ . '/../Fixture/StaticPropertyGetSet.php';
        require_once $staticPropertyGetSetFixture;

        $classReflection    = (new DefaultReflector(new SingleFileSourceLocator($staticPropertyGetSetFixture, $this->astLocator)))->reflectClass(StaticPropertyGetSet::class);
        $propertyReflection = $classReflection->getProperty('baz');

        $propertyReflection->setValue('first', 'second');

        self::assertSame('second', StaticPropertyGetSet::$baz);
        self::assertSame('second', $propertyReflection->getValue());
    }

    public function testSetValueOfStaticPropertyThrowsExceptionWhenClassDoesNotExist(): void
    {
        $php = <<<'PHP'
<?php
class Foo
{
    public static $boo = 'boo';
}
PHP;

        $classReflection    = (new DefaultReflector(new StringSourceLocator($php, $this->astLocator)))->reflectClass('Foo');
        $propertyReflection = $classReflection->getProperty('boo');

        $this->expectException(ClassDoesNotExist::class);

        $propertyReflection->setValue(null);
    }

    public function testGetValueOfStaticPropertyThrowsExceptionWhenClassDoesNotExist(): void
    {
        $php = <<<'PHP'
<?php
class Foo
{
    public static $boo = 'boo';
}
PHP;

        $classReflection    = (new DefaultReflector(new StringSourceLocator($php, $this->astLocator)))->reflectClass('Foo');
        $propertyReflection = $classReflection->getProperty('boo');

        $this->expectException(ClassDoesNotExist::class);

        $propertyReflection->getValue();
    }

    public function testSetAccessibleAndSetAndGetValueOfStaticProperty(): void
    {
        $staticPropertyGetSetFixtureFile = __DIR__ . '/../Fixture/StaticPropertyGetSet.php';
        require_once $staticPropertyGetSetFixtureFile;

        $classReflection    = (new DefaultReflector(new SingleFileSourceLocator($staticPropertyGetSetFixtureFile, $this->astLocator)))->reflectClass(StaticPropertyGetSet::class);
        $propertyReflection = $classReflection->getProperty('bat');

        $object = new PropertyGetSet();

        $propertyReflection->setValue($object, 'batman');

        self::assertSame('batman', $propertyReflection->getValue($object));
    }

    public function testSetAndGetValueOfObjectProperty(): void
    {
        $propertyGetSetFixture = __DIR__ . '/../Fixture/PropertyGetSet.php';
        require_once $propertyGetSetFixture;

        $classReflection    = (new DefaultReflector(new SingleFileSourceLocator($propertyGetSetFixture, $this->astLocator)))->reflectClass(PropertyGetSet::class);
        $propertyReflection = $classReflection->getProperty('baz');

        $object = new PropertyGetSet();

        $propertyReflection->setValue($object, 'value');

        self::assertSame('value', $object->baz);
        self::assertSame('value', $propertyReflection->getValue($object));
    }

    public function testSetValueOfObjectPropertyThrowsExceptionWhenNoObject(): void
    {
        $classReflection    = (new DefaultReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/PropertyGetSet.php', $this->astLocator)))->reflectClass(PropertyGetSet::class);
        $propertyReflection = $classReflection->getProperty('baz');

        $this->expectException(NoObjectProvided::class);

        $propertyReflection->setValue(null);
    }

    public function testGetValueOfObjectPropertyThrowsExceptionWhenNoObject(): void
    {
        $classReflection    = (new DefaultReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/PropertyGetSet.php', $this->astLocator)))->reflectClass(PropertyGetSet::class);
        $propertyReflection = $classReflection->getProperty('baz');

        $this->expectException(NoObjectProvided::class);

        $propertyReflection->getValue();
    }

    public function testSetValueOfObjectPropertyThrowsExceptionWhenNotAnObject(): void
    {
        $classReflection    = (new DefaultReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/PropertyGetSet.php', $this->astLocator)))->reflectClass(PropertyGetSet::class);
        $propertyReflection = $classReflection->getProperty('baz');

        $this->expectException(NotAnObject::class);

        $propertyReflection->setValue('string');
    }

    public function testSetValueOfObjectPropertyThrowsExceptionWhenObjectNotInstanceOfClass(): void
    {
        $classReflection    = (new DefaultReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/PropertyGetSet.php', $this->astLocator)))->reflectClass(PropertyGetSet::class);
        $propertyReflection = $classReflection->getProperty('baz');

        $this->expectException(ObjectNotInstanceOfClass::class);

        $propertyReflection->setValue(new stdClass());
    }

    public function testGetValueOfObjectPropertyThrowsExceptionObjectNotInstanceOfClass(): void
    {
        $classReflection    = (new DefaultReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/PropertyGetSet.php', $this->astLocator)))->reflectClass(PropertyGetSet::class);
        $propertyReflection = $classReflection->getProperty('baz');

        $this->expectException(ObjectNotInstanceOfClass::class);

        $propertyReflection->getValue(new stdClass());
    }

    public function testSetAccessibleAndSetAndGetValueOfObjectProperty(): void
    {
        $propertyGetSetFixtureFile = __DIR__ . '/../Fixture/PropertyGetSet.php';
        require_once $propertyGetSetFixtureFile;

        $classReflection    = (new DefaultReflector(new SingleFileSourceLocator($propertyGetSetFixtureFile, $this->astLocator)))->reflectClass(PropertyGetSet::class);
        $propertyReflection = $classReflection->getProperty('bat');

        $object = new PropertyGetSet();

        $propertyReflection->setValue($object, 'batman');

        self::assertSame('batman', $propertyReflection->getValue($object));
    }

    public function testAllowsNull(): void
    {
        $classReflection = $this->reflector->reflectClass(Php74PropertyTypeDeclarations::class);

        $integerPropertyReflection = $classReflection->getProperty('integerProperty');
        self::assertFalse($integerPropertyReflection->allowsNull());

        $noTypePropertyReflection = $classReflection->getProperty('noTypeProperty');
        self::assertTrue($noTypePropertyReflection->allowsNull());

        $nullableStringPropertyReflection = $classReflection->getProperty('nullableStringProperty');
        self::assertTrue($nullableStringPropertyReflection->allowsNull());
    }

    /**
     * @return array
     */
    public function hasTypeProvider(): array
    {
        return [
            ['integerProperty', true],
            ['classProperty', true],
            ['noTypeProperty', false],
            ['nullableStringProperty', true],
            ['arrayProperty', true],
        ];
    }

    /**
     * @dataProvider hasTypeProvider
     */
    public function testHasType(
        string $propertyName,
        bool $expectedHasType,
    ): void {
        $classReflection    = $this->reflector->reflectClass(Php74PropertyTypeDeclarations::class);
        $propertyReflection = $classReflection->getProperty($propertyName);

        self::assertSame($expectedHasType, $propertyReflection->hasType());
    }

    /**
     * @return array
     */
    public function getTypeProvider(): array
    {
        return [
            ['integerProperty', 'int'],
            ['classProperty', 'stdClass'],
            ['noTypeProperty', ''],
            ['nullableStringProperty', '?string'],
            ['arrayProperty', 'array'],
        ];
    }

    /**
     * @dataProvider getTypeProvider
     */
    public function testGetType(
        string $propertyName,
        string $expectedType,
    ): void {
        $classReflection    = $this->reflector->reflectClass(Php74PropertyTypeDeclarations::class);
        $propertyReflection = $classReflection->getProperty($propertyName);

        $type = $propertyReflection->getType();

        self::assertSame($expectedType, (string) $type);
    }

    /**
     * @runInSeparateProcess
     */
    public function testSetType(): void
    {
        $classReflection    = $this->reflector->reflectClass(Php74PropertyTypeDeclarations::class);
        $propertyReflection = $classReflection->getProperty('integerProperty');

        $propertyReflection->setType('string');

        self::assertSame('string', (string) $propertyReflection->getType());
        self::assertStringStartsWith(
            'public string $integerProperty',
            (new StandardPrettyPrinter())->prettyPrint([$propertyReflection->getAst()]),
        );
    }

    /**
     * @runInSeparateProcess
     */
    public function testRemoveType(): void
    {
        $classReflection    = $this->reflector->reflectClass(Php74PropertyTypeDeclarations::class);
        $propertyReflection = $classReflection->getProperty('integerProperty');

        $propertyReflection->removeType();

        self::assertNull($propertyReflection->getType());
        self::assertStringStartsWith(
            'public $integerProperty',
            (new StandardPrettyPrinter())->prettyPrint([$propertyReflection->getAst()]),
        );
    }

    public function isInitializedProvider(): array
    {
        $object                  = new InitializedProperties();
        $object::$staticWithType = 0;

        return [
            ['withoutType', $object, true],
            ['staticWithoutType', null, true],
            ['withType', $object, false],
            ['staticWithType', null, false],
            ['staticWithType', $object, true],
            ['staticWithTypeAndDefault', null, true],
            ['withTypeInitialized', $object, true],
        ];
    }

    /**
     * @dataProvider isInitializedProvider
     */
    public function testIsInitialized(string $propertyName, ?object $object, bool $isInitialized): void
    {
        $classReflection = $this->reflector->reflectClass(InitializedProperties::class);

        self::assertSame($isInitialized, $classReflection->getProperty($propertyName)->isInitialized($object));
    }

    public function testIsInitializedThrowsTypeError(): void
    {
        $classReflection = $this->reflector->reflectClass(InitializedProperties::class);

        self::expectException(ObjectNotInstanceOfClass::class);

        $classReflection->getProperty('withoutType')->isInitialized(new stdClass());
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
        class Foo {
            %s
            public $foo = "foo";
        }', $docComment);

        $reflector          = new DefaultReflector(new StringSourceLocator($php, $this->astLocator));
        $classReflection    = $reflector->reflectClass('Foo');
        $propertyReflection = $classReflection->getProperty('foo');

        self::assertSame($isDeprecated, $propertyReflection->isDeprecated());
    }

    public function testGetAttributesWithoutAttributes(): void
    {
        $reflector          = new DefaultReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/ExampleClass.php', $this->astLocator));
        $classReflection    = $reflector->reflectClass(ExampleClass::class);
        $propertyReflection = $classReflection->getProperty('privateProperty');
        $attributes         = $propertyReflection->getAttributes();

        self::assertCount(0, $attributes);
    }

    public function testGetAttributesWithAttributes(): void
    {
        $reflector          = new DefaultReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/Attributes.php', $this->astLocator));
        $classReflection    = $reflector->reflectClass(ClassWithAttributes::class);
        $propertyReflection = $classReflection->getProperty('propertyWithAttributes');
        $attributes         = $propertyReflection->getAttributes();

        self::assertCount(2, $attributes);
    }

    public function testGetAttributesByName(): void
    {
        $reflector          = new DefaultReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/Attributes.php', $this->astLocator));
        $classReflection    = $reflector->reflectClass(ClassWithAttributes::class);
        $propertyReflection = $classReflection->getProperty('propertyWithAttributes');
        $attributes         = $propertyReflection->getAttributesByName(Attr::class);

        self::assertCount(1, $attributes);
    }

    public function testGetAttributesByInstance(): void
    {
        $reflector          = new DefaultReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/Attributes.php', $this->astLocator));
        $classReflection    = $reflector->reflectClass(ClassWithAttributes::class);
        $propertyReflection = $classReflection->getProperty('propertyWithAttributes');
        $attributes         = $propertyReflection->getAttributesByInstance(Attr::class);

        self::assertCount(2, $attributes);
    }
}
