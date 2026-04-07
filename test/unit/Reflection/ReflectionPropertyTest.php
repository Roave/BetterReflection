<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection;

use ClassWithPropertiesAndTraitProperties;
use Composer\Autoload\ClassLoader;
use Error;
use ExtendedClassWithPropertiesAndTraitProperties;
use OutOfBoundsException;
use PhpParser\Modifiers;
use PhpParser\Node;
use PhpParser\Node\PropertyItem;
use PhpParser\Node\Stmt\Property;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use ReflectionProperty as CoreReflectionProperty;
use Roave\BetterReflection\Reflection\Adapter\ReflectionProperty as ReflectionPropertyAdapter;
use Roave\BetterReflection\Reflection\Exception\ClassDoesNotExist;
use Roave\BetterReflection\Reflection\Exception\CodeLocationMissing;
use Roave\BetterReflection\Reflection\Exception\NoObjectProvided;
use Roave\BetterReflection\Reflection\Exception\NotAnObject;
use Roave\BetterReflection\Reflection\Exception\ObjectNotInstanceOfClass;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use Roave\BetterReflection\Reflection\ReflectionNamedType;
use Roave\BetterReflection\Reflection\ReflectionParameter;
use Roave\BetterReflection\Reflection\ReflectionProperty;
use Roave\BetterReflection\Reflection\ReflectionPropertyHookType;
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
use Roave\BetterReflectionTest\Fixture\ClassUsesTraitStaticPropertyGetSet;
use Roave\BetterReflectionTest\Fixture\ClassWithAttributes;
use Roave\BetterReflectionTest\Fixture\DefaultProperties;
use Roave\BetterReflectionTest\Fixture\ExampleClass;
use Roave\BetterReflectionTest\Fixture\InitializedProperties;
use Roave\BetterReflectionTest\Fixture\Php74PropertyTypeDeclarations;
use Roave\BetterReflectionTest\Fixture\PropertyGetSet;
use Roave\BetterReflectionTest\Fixture\ReadonlyExampleClass;
use Roave\BetterReflectionTest\Fixture\StaticPropertyGetSet;
use Roave\BetterReflectionTest\Fixture\StringEnum;
use stdClass;
use TraitWithProperty;

use function assert;
use function sprintf;

#[CoversClass(ReflectionProperty::class)]
#[CoversClass(ReflectionPropertyHookType::class)]
class ReflectionPropertyTest extends TestCase
{
    private Reflector $reflector;

    private Locator $astLocator;

    public function setUp(): void
    {
        parent::setUp();

        $loader = $GLOBALS['loader'];
        assert($loader instanceof ClassLoader);
        $this->astLocator = BetterReflectionSingleton::instance()->astLocator();
        $this->reflector  = new DefaultReflector(new ComposerSourceLocator($loader, $this->astLocator));
    }

    public function testCreateFromName(): void
    {
        $property = ReflectionProperty::createFromName(ReflectionProperty::class, 'name');

        self::assertInstanceOf(ReflectionProperty::class, $property);
        self::assertSame('name', $property->getName());
    }

    public function testCreateFromNameThrowsExceptionWhenPropertyDoesNotExist(): void
    {
        $this->expectException(OutOfBoundsException::class);
        ReflectionProperty::createFromName(ReflectionProperty::class, 'notExist');
    }

    public function testCreateFromInstance(): void
    {
        $property = ReflectionProperty::createFromInstance(new ClassForHinting(), 'someProperty');

        self::assertInstanceOf(ReflectionProperty::class, $property);
        self::assertSame('someProperty', $property->getName());
    }

    public function testCreateFromInstanceThrowsExceptionWhenPropertyDoesNotExist(): void
    {
        $this->expectException(OutOfBoundsException::class);
        ReflectionProperty::createFromInstance(new ClassForHinting(), 'notExist');
    }

    public function testCreateFromNodeWithNotPromotedProperty(): void
    {
        $classInfo            = $this->reflector->reflectClass(ExampleClass::class);
        $propertyPropertyNode = new PropertyItem('foo');
        $property             = ReflectionProperty::createFromNode(
            $this->reflector,
            new Property(Modifiers::PUBLIC, [$propertyPropertyNode]),
            $propertyPropertyNode,
            $classInfo,
            $classInfo,
        );

        self::assertFalse($property->isPromoted());
    }

    public function testCreateFromNodeWithPromotedProperty(): void
    {
        $classInfo            = $this->reflector->reflectClass(ExampleClass::class);
        $propertyPropertyNode = new PropertyItem('foo');
        $property             = ReflectionProperty::createFromNode(
            $this->reflector,
            new Property(Modifiers::PUBLIC, [$propertyPropertyNode]),
            $propertyPropertyNode,
            $classInfo,
            $classInfo,
            true,
        );

        self::assertTrue($property->isPromoted());
    }

    public function testVisibilityMethods(): void
    {
        $classInfo = $this->reflector->reflectClass(ExampleClass::class);

        $onlyPrivateProp = $classInfo->getProperty('privateProperty');
        self::assertTrue($onlyPrivateProp->isPrivate());
        self::assertFalse($onlyPrivateProp->isStatic());
        self::assertFalse($onlyPrivateProp->isReadOnly());

        $onlyProtectedProp = $classInfo->getProperty('protectedProperty');
        self::assertTrue($onlyProtectedProp->isProtected());
        self::assertFalse($onlyProtectedProp->isStatic());
        self::assertFalse($onlyProtectedProp->isReadOnly());

        $onlyPublicProp = $classInfo->getProperty('publicProperty');
        self::assertTrue($onlyPublicProp->isPublic());
        self::assertFalse($onlyPublicProp->isStatic());
        self::assertFalse($onlyPublicProp->isReadOnly());
    }

    public function isImplicitPublic(): void
    {
        $php = <<<'PHP'
            <?php

            class Foo
            {
                $boo = 'boo';
            }
        PHP;

        $classReflection    = (new DefaultReflector(new StringSourceLocator($php, $this->astLocator)))->reflectClass('Foo');
        $propertyReflection = $classReflection->getProperty('boo');

        self::assertTrue($propertyReflection->isPublic());
        self::assertSame(CoreReflectionProperty::IS_PUBLIC, $propertyReflection->getModifiers());
    }

    public function testIsStatic(): void
    {
        $classInfo = $this->reflector->reflectClass(ExampleClass::class);

        $publicProp = $classInfo->getProperty('publicProperty');
        self::assertFalse($publicProp->isStatic());

        $publiStaticProp = $classInfo->getProperty('publicStaticProperty');
        self::assertTrue($publiStaticProp->isPublic());
        self::assertTrue($publiStaticProp->isStatic());

        $protectedStaticProp = $classInfo->getProperty('protectedStaticProperty');
        self::assertTrue($protectedStaticProp->isProtected());
        self::assertTrue($protectedStaticProp->isStatic());

        $privateStaticProp = $classInfo->getProperty('privateStaticProperty');
        self::assertTrue($privateStaticProp->isPrivate());
        self::assertTrue($privateStaticProp->isStatic());
    }

    public function testIsReadOnly(): void
    {
        $classInfo = $this->reflector->reflectClass(ExampleClass::class);

        $notReadOnlyProperty = $classInfo->getProperty('publicProperty');
        self::assertFalse($notReadOnlyProperty->isReadOnly());

        $readOnlyProperty = $classInfo->getProperty('readOnlyProperty');
        self::assertTrue($readOnlyProperty->isPublic());
        self::assertTrue($readOnlyProperty->isReadOnly());
    }

    public function testIsFinal(): void
    {
        $classInfo = $this->reflector->reflectClass(ExampleClass::class);

        $notReadOnlyProperty = $classInfo->getProperty('publicProperty');
        self::assertFalse($notReadOnlyProperty->isFinal());

        $finalPublicProperty = $classInfo->getProperty('finalPublicProperty');
        self::assertTrue($finalPublicProperty->isFinal());
        self::assertTrue($finalPublicProperty->isPublic());
    }

    public function testIsNotAbstract(): void
    {
        $classInfo = $this->reflector->reflectClass(ExampleClass::class);

        $notAbstractProperty = $classInfo->getProperty('publicProperty');
        self::assertFalse($notAbstractProperty->isAbstract());
    }

    public function testIsReadOnlyInReadOnlyClass(): void
    {
        $reflector = new DefaultReflector(new SingleFileSourceLocator(
            __DIR__ . '/../Fixture/ExampleClass.php',
            $this->astLocator,
        ));
        $classInfo = $reflector->reflectClass('\\Roave\\BetterReflectionTest\\Fixture\\ReadOnlyClass');

        $property = $classInfo->getProperty('property');
        self::assertTrue($property->isReadOnly());
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
            class Bar {
                /* A comment  */
                /** Property description */
                /* An another comment */
                public $prop;
            }
        ';
        $reflector = (new DefaultReflector(new StringSourceLocator($php, $this->astLocator)))->reflectClass('Bar');
        $property  = $reflector->getProperty('prop');

        self::assertNotNull($property?->getDocComment());
        self::assertStringContainsString('Property description', $property->getDocComment());
    }

    public function testGetDocCommentReturnsNullWithNoComment(): void
    {
        $classInfo = $this->reflector->reflectClass(ExampleClass::class);
        $property  = $classInfo->getProperty('publicStaticProperty');

        self::assertNull($property->getDocComment());
    }

    /** @return list<array{0: non-empty-string, 1: int-mask-of<ReflectionPropertyAdapter::IS_*>}> */
    public static function modifierProvider(): array
    {
        return [
            ['publicProperty', CoreReflectionProperty::IS_PUBLIC],
            ['protectedProperty', CoreReflectionProperty::IS_PROTECTED],
            ['privateProperty', CoreReflectionProperty::IS_PRIVATE],
            ['publicStaticProperty', CoreReflectionProperty::IS_PUBLIC | CoreReflectionProperty::IS_STATIC],
            ['readOnlyProperty', CoreReflectionProperty::IS_PUBLIC | ReflectionPropertyAdapter::IS_READONLY | ReflectionPropertyAdapter::IS_PROTECTED_SET_COMPATIBILITY],
            ['finalPublicProperty', CoreReflectionProperty::IS_PUBLIC | ReflectionPropertyAdapter::IS_FINAL_COMPATIBILITY],
        ];
    }

    /** @param non-empty-string $propertyName */
    #[DataProvider('modifierProvider')]
    public function testGetModifiers(string $propertyName, int $expectedModifier): void
    {
        $classInfo = $this->reflector->reflectClass(ExampleClass::class);
        $property  = $classInfo->getProperty($propertyName);

        self::assertSame($expectedModifier, $property->getModifiers());
    }

    /** @return list<array{0: non-empty-string, 1: int-mask-of<ReflectionPropertyAdapter::IS_*>}> */
    public static function modifierInReadonlyClassProvider(): array
    {
        return [
            ['publicProperty', CoreReflectionProperty::IS_PUBLIC | ReflectionPropertyAdapter::IS_PROTECTED_SET_COMPATIBILITY],
            ['protectedProperty', CoreReflectionProperty::IS_PROTECTED],
            ['privateProperty', CoreReflectionProperty::IS_PRIVATE],
        ];
    }

    /** @param non-empty-string $propertyName */
    #[DataProvider('modifierInReadonlyClassProvider')]
    public function testGetModifiersInReadonlyClass(string $propertyName, int $expectedModifier): void
    {
        $classInfo = $this->reflector->reflectClass(ReadonlyExampleClass::class);
        $property  = $classInfo->getProperty($propertyName);

        self::assertSame($expectedModifier, $property->getModifiers());
    }

    public function testIsPromoted(): void
    {
        $classInfo = $this->reflector->reflectClass(ExampleClass::class);

        $promotedProperty = $classInfo->getProperty('promotedProperty');

        self::assertTrue($promotedProperty->isPromoted());
        self::assertTrue($promotedProperty->isPrivate());
        self::assertTrue($promotedProperty->hasType());
        self::assertSame('int|null', $promotedProperty->getType()->__toString());
        self::assertFalse($promotedProperty->hasDefaultValue());
        self::assertNull($promotedProperty->getDefaultValue());
        self::assertSame(54, $promotedProperty->getStartLine());
        self::assertSame(54, $promotedProperty->getEndLine());
        self::assertSame(60, $promotedProperty->getStartColumn());
        self::assertSame(95, $promotedProperty->getEndColumn());
        self::assertSame('/** Some doccomment */', $promotedProperty->getDocComment());
    }

    public function testIsDefaultAndIsDynamic(): void
    {
        $classInfo = $this->reflector->reflectClass(ExampleClass::class);

        $publicProperty = $classInfo->getProperty('publicProperty');

        self::assertTrue($publicProperty->isDefault());
        self::assertFalse($publicProperty->isDynamic());

        $publicStaticProperty = $classInfo->getProperty('publicStaticProperty');

        self::assertTrue($publicStaticProperty->isDefault());
        self::assertFalse($publicStaticProperty->isDynamic());
    }

    public function testIsDefaultAndIsDynamicWithRuntimeDeclaredProperty(): void
    {
        $classInfo            = $this->reflector->reflectClass(ExampleClass::class);
        $propertyPropertyNode = new PropertyItem('foo');
        $propertyNode         = ReflectionProperty::createFromNode(
            $this->reflector,
            new Property(Modifiers::PUBLIC, [$propertyPropertyNode]),
            $propertyPropertyNode,
            $classInfo,
            $classInfo,
            false,
            false,
        );

        self::assertFalse($propertyNode->isDefault());
        self::assertTrue($propertyNode->isDynamic());
    }

    public function testToString(): void
    {
        $classInfo = $this->reflector->reflectClass(ExampleClass::class);
        self::assertSame("/**\n     * @var string\n     */\nProperty [ <default> public \$publicProperty ]", (string) $classInfo->getProperty('publicProperty'));
    }

    /** @return list<array{0: non-empty-string, 1: bool, 2: mixed, 3: class-string|null}> */
    public static function propertyDefaultValueProvider(): array
    {
        return [
            ['hasDefault', true, 'const', Node\Expr::class],
            ['hasNullAsDefault', true, null, Node\Expr::class],
            ['noDefault', true, null, null],
            ['hasDefaultWithType', true, 123, Node\Expr::class],
            ['hasNullAsDefaultWithType', true, null, Node\Expr::class],
            ['noDefaultWithType', false, null, null],
            ['fromTrait', true, 'anything', Node\Expr::class],
        ];
    }

    /**
     * @param non-empty-string  $propertyName
     * @param class-string|null $defaultValueExpression
     */
    #[DataProvider('propertyDefaultValueProvider')]
    public function testPropertyDefaultValue(string $propertyName, bool $hasDefaultValue, mixed $defaultValue, string|null $defaultValueExpression): void
    {
        $classInfo = (new DefaultReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/DefaultProperties.php', $this->astLocator)))->reflectClass(DefaultProperties::class);
        $property  = $classInfo->getProperty($propertyName);

        self::assertSame($hasDefaultValue, $property->hasDefaultValue());
        self::assertSame($defaultValue, $property->getDefaultValue());

        if ($defaultValueExpression !== null) {
            self::assertInstanceOf($defaultValueExpression, $property->getDefaultValueExpression());
        } else {
            self::assertNull($property->getDefaultValueExpression());
        }
    }

    /** @param non-empty-string $php */
    #[DataProvider('startEndLineProvider')]
    public function testStartEndLine(string $php, int $startLine, int $endLine): void
    {
        $reflector       = new DefaultReflector(new StringSourceLocator($php, $this->astLocator));
        $classReflection = $reflector->reflectClass('\T');
        $constReflection = $classReflection->getProperty('test');
        self::assertEquals($startLine, $constReflection->getStartLine());
        self::assertEquals($endLine, $constReflection->getEndLine());
    }

    /** @return list<array{0: non-empty-string, 1: int, 2: int}> */
    public static function startEndLineProvider(): array
    {
        return [
            ["<?php\nclass T {\npublic \$test = 1; }", 3, 3],
            ["<?php\n\nclass T {\npublic \$test = 1; }", 4, 4],
            ["<?php\nclass T {\npublic \$test = \n1; }", 3, 4],
            ["<?php\nclass T {\npublic \n\$test = 1; }", 3, 4],
        ];
    }

    /** @return list<array{0: non-empty-string, 1: int, 2: int}> */
    public static function columnsProvider(): array
    {
        return [
            ["<?php\n\nclass T {\npublic \$test = 1;\n}", 1, 17],
            ["<?php\n\n    class T {\n        protected \$test = 1;\n    }", 9, 28],
            ['<?php class T {private $test = 1;}', 16, 33],
        ];
    }

    /** @param non-empty-string $php */
    #[DataProvider('columnsProvider')]
    public function testGetStartColumnAndEndColumn(string $php, int $startColumn, int $endColumn): void
    {
        $reflector          = new DefaultReflector(new StringSourceLocator($php, $this->astLocator));
        $classReflection    = $reflector->reflectClass('T');
        $constantReflection = $classReflection->getProperty('test');

        self::assertEquals($startColumn, $constantReflection->getStartColumn());
        self::assertEquals($endColumn, $constantReflection->getEndColumn());
    }

    public function testGetStartLineThrowsExceptionForMagicallyAddedEnumProperty(): void
    {
        $reflector = new DefaultReflector(new AggregateSourceLocator([
            new SingleFileSourceLocator(__DIR__ . '/../Fixture/Enums.php', $this->astLocator),
            BetterReflectionSingleton::instance()->sourceLocator(),
        ]));

        $classReflection    = $reflector->reflectClass(StringEnum::class);
        $propertyReflection = $classReflection->getProperty('name');

        $this->expectException(CodeLocationMissing::class);
        $propertyReflection->getStartLine();
    }

    public function testGetEndLineThrowsExceptionForMagicallyAddedEnumProperty(): void
    {
        $reflector = new DefaultReflector(new AggregateSourceLocator([
            new SingleFileSourceLocator(__DIR__ . '/../Fixture/Enums.php', $this->astLocator),
            BetterReflectionSingleton::instance()->sourceLocator(),
        ]));

        $classReflection    = $reflector->reflectClass(StringEnum::class);
        $propertyReflection = $classReflection->getProperty('name');

        $this->expectException(CodeLocationMissing::class);
        $propertyReflection->getEndLine();
    }

    public function testGetStartColumnThrowsExceptionForMagicallyAddedEnumProperty(): void
    {
        $reflector = new DefaultReflector(new AggregateSourceLocator([
            new SingleFileSourceLocator(__DIR__ . '/../Fixture/Enums.php', $this->astLocator),
            BetterReflectionSingleton::instance()->sourceLocator(),
        ]));

        $classReflection    = $reflector->reflectClass(StringEnum::class);
        $propertyReflection = $classReflection->getProperty('name');

        $this->expectException(CodeLocationMissing::class);
        $propertyReflection->getStartColumn();
    }

    public function testGetEndColumnThrowsExceptionForMagicallyAddedEnumProperty(): void
    {
        $reflector = new DefaultReflector(new AggregateSourceLocator([
            new SingleFileSourceLocator(__DIR__ . '/../Fixture/Enums.php', $this->astLocator),
            BetterReflectionSingleton::instance()->sourceLocator(),
        ]));

        $classReflection    = $reflector->reflectClass(StringEnum::class);
        $propertyReflection = $classReflection->getProperty('name');

        $this->expectException(CodeLocationMissing::class);
        $propertyReflection->getEndColumn();
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

    #[RunInSeparateProcess]
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

    #[RunInSeparateProcess]
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

    /** @return list<array{0: non-empty-string, 1: bool}> */
    public static function hasTypeProvider(): array
    {
        return [
            ['integerProperty', true],
            ['classProperty', true],
            ['noTypeProperty', false],
            ['nullableStringProperty', true],
            ['arrayProperty', true],
        ];
    }

    /** @param non-empty-string $propertyName */
    #[DataProvider('hasTypeProvider')]
    public function testHasType(
        string $propertyName,
        bool $expectedHasType,
    ): void {
        $classReflection    = $this->reflector->reflectClass(Php74PropertyTypeDeclarations::class);
        $propertyReflection = $classReflection->getProperty($propertyName);

        self::assertSame($expectedHasType, $propertyReflection->hasType());
    }

    /** @return list<array{0: non-empty-string, 1: string}> */
    public static function getTypeProvider(): array
    {
        return [
            ['integerProperty', 'int'],
            ['classProperty', 'stdClass'],
            ['noTypeProperty', ''],
            ['nullableStringProperty', 'string|null'],
            ['arrayProperty', 'array'],
        ];
    }

    /** @param non-empty-string $propertyName */
    #[DataProvider('getTypeProvider')]
    public function testGetType(
        string $propertyName,
        string $expectedType,
    ): void {
        $classReflection    = $this->reflector->reflectClass(Php74PropertyTypeDeclarations::class);
        $propertyReflection = $classReflection->getProperty($propertyName);

        $type = $propertyReflection->getType();

        self::assertSame($expectedType, (string) $type);
    }

    /** @return list<array{0: non-empty-string, 1: object|null, 2: bool}> */
    public static function isInitializedProvider(): array
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

    /** @param non-empty-string $propertyName */
    #[DataProvider('isInitializedProvider')]
    public function testIsInitialized(string $propertyName, object|null $object, bool $isInitialized): void
    {
        $classReflection = $this->reflector->reflectClass(InitializedProperties::class);

        self::assertSame($isInitialized, $classReflection->getProperty($propertyName)->isInitialized($object));
    }

    public function testIsInitializedThrowsTypeError(): void
    {
        $classReflection = $this->reflector->reflectClass(InitializedProperties::class);

        $this->expectException(ObjectNotInstanceOfClass::class);

        $classReflection->getProperty('withoutType')->isInitialized(new stdClass());
    }

    public function testIsInitializedThrowsError(): void
    {
        $object = new InitializedProperties();
        unset($object->toBeRemoved);

        $classReflection = $this->reflector->reflectClass(InitializedProperties::class);

        $this->expectException(Error::class);
        $this->expectExceptionMessage('Removed property');
        $classReflection->getProperty('toBeRemoved')->isInitialized($object);
    }

    /** @return list<array{0: string, 1: bool}> */
    public static function deprecatedDocCommentProvider(): array
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

    #[DataProvider('deprecatedDocCommentProvider')]
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

    /** @return list<array{0: non-empty-string}> */
    public static function dataGetAttributes(): array
    {
        return [
            ['propertyWithAttributes'],
            ['promotedPropertyWithAttributes'],
        ];
    }

    /** @param non-empty-string $propertyName */
    #[DataProvider('dataGetAttributes')]
    public function testGetAttributesWithAttributes(string $propertyName): void
    {
        $reflector          = new DefaultReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/Attributes.php', $this->astLocator));
        $classReflection    = $reflector->reflectClass(ClassWithAttributes::class);
        $propertyReflection = $classReflection->getProperty($propertyName);
        $attributes         = $propertyReflection->getAttributes();

        self::assertCount(2, $attributes);
    }

    /** @param non-empty-string $propertyName */
    #[DataProvider('dataGetAttributes')]
    public function testGetAttributesByName(string $propertyName): void
    {
        $reflector          = new DefaultReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/Attributes.php', $this->astLocator));
        $classReflection    = $reflector->reflectClass(ClassWithAttributes::class);
        $propertyReflection = $classReflection->getProperty($propertyName);
        $attributes         = $propertyReflection->getAttributesByName(Attr::class);

        self::assertCount(1, $attributes);
    }

    /** @param non-empty-string $propertyName */
    #[DataProvider('dataGetAttributes')]
    public function testGetAttributesByInstance(string $propertyName): void
    {
        $reflector          = new DefaultReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/Attributes.php', $this->astLocator));
        $classReflection    = $reflector->reflectClass(ClassWithAttributes::class);
        $propertyReflection = $classReflection->getProperty($propertyName);
        $attributes         = $propertyReflection->getAttributesByInstance(Attr::class);

        self::assertCount(2, $attributes);
    }

    public function testWithImplementingClass(): void
    {
        $reflector          = new DefaultReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/Attributes.php', $this->astLocator));
        $classReflection    = $reflector->reflectClass(ClassWithAttributes::class);
        $propertyReflection = $classReflection->getProperty('propertyWithAttributes');
        $attributes         = $propertyReflection->getAttributes();

        self::assertCount(2, $attributes);

        $implementingClassReflection = self::createStub(ReflectionClass::class);

        $clonePropertyReflection = $propertyReflection->withImplementingClass($implementingClassReflection);

        self::assertNotSame($propertyReflection, $clonePropertyReflection);
        self::assertSame($propertyReflection->getDeclaringClass(), $clonePropertyReflection->getDeclaringClass());
        self::assertNotSame($propertyReflection->getImplementingClass(), $clonePropertyReflection->getImplementingClass());
        self::assertNotSame($propertyReflection->getType(), $clonePropertyReflection->getType());

        $cloneAttributes = $clonePropertyReflection->getAttributes();

        self::assertCount(2, $cloneAttributes);
        self::assertNotSame($attributes[0], $cloneAttributes[0]);
    }

    /** @return list<array{0: non-empty-string, 1: int-mask-of<ReflectionPropertyAdapter::IS_*>}> */
    public static function asymmetricVisibilityModifierProvider(): array
    {
        return [
            ['publicPublicSet', CoreReflectionProperty::IS_PUBLIC],
            ['publicProtectedSet', CoreReflectionProperty::IS_PUBLIC | ReflectionPropertyAdapter::IS_PROTECTED_SET_COMPATIBILITY],
            ['publicPrivateSet', CoreReflectionProperty::IS_PUBLIC | ReflectionPropertyAdapter::IS_PRIVATE_SET_COMPATIBILITY | ReflectionPropertyAdapter::IS_FINAL_COMPATIBILITY],
            ['protectedProtectedSet', CoreReflectionProperty::IS_PROTECTED],
            ['protectedPrivateSet', CoreReflectionProperty::IS_PROTECTED | ReflectionPropertyAdapter::IS_PRIVATE_SET_COMPATIBILITY | ReflectionPropertyAdapter::IS_FINAL_COMPATIBILITY],
            ['privatePrivateSet', CoreReflectionProperty::IS_PRIVATE],
            ['promotedPublicPublicSet', CoreReflectionProperty::IS_PUBLIC],
            ['promotedPublicProtectedSet', CoreReflectionProperty::IS_PUBLIC | ReflectionPropertyAdapter::IS_PROTECTED_SET_COMPATIBILITY],
            ['promotedPublicPrivateSet', CoreReflectionProperty::IS_PUBLIC | ReflectionPropertyAdapter::IS_PRIVATE_SET_COMPATIBILITY | ReflectionPropertyAdapter::IS_FINAL_COMPATIBILITY],
            ['promotedProtectedProtectedSet', CoreReflectionProperty::IS_PROTECTED],
            ['promotedProtectedPrivateSet', CoreReflectionProperty::IS_PROTECTED | ReflectionPropertyAdapter::IS_PRIVATE_SET_COMPATIBILITY | ReflectionPropertyAdapter::IS_FINAL_COMPATIBILITY],
            ['promotedPrivatePrivateSet', CoreReflectionProperty::IS_PRIVATE],
        ];
    }

    /** @param non-empty-string $propertyName */
    #[DataProvider('asymmetricVisibilityModifierProvider')]
    public function testGetAsymmetricVisibilityMethods(string $propertyName, int $expectedModifier): void
    {
        $reflector = new DefaultReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/AsymmetricVisibilityClass.php', $this->astLocator));
        $classInfo = $reflector->reflectClass('Roave\BetterReflectionTest\Fixture\AsymmetricVisibilityClass');
        $property  = $classInfo->getProperty($propertyName);

        self::assertSame($expectedModifier, $property->getModifiers());
    }

    public function testIsProtectedSet(): void
    {
        $reflector = new DefaultReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/AsymmetricVisibilityClass.php', $this->astLocator));
        $classInfo = $reflector->reflectClass('Roave\BetterReflectionTest\Fixture\AsymmetricVisibilityClass');

        $publicPublicSetProperty    = $classInfo->getProperty('publicPublicSet');
        $publicProtectedSetProperty = $classInfo->getProperty('publicProtectedSet');

        self::assertFalse($publicPublicSetProperty->isProtectedSet());
        self::assertTrue($publicProtectedSetProperty->isProtectedSet());
    }

    public function testIsPrivateSet(): void
    {
        $reflector = new DefaultReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/AsymmetricVisibilityClass.php', $this->astLocator));
        $classInfo = $reflector->reflectClass('Roave\BetterReflectionTest\Fixture\AsymmetricVisibilityClass');

        $protectedProtectedSet = $classInfo->getProperty('protectedProtectedSet');
        $protectedPrivateSet   = $classInfo->getProperty('protectedPrivateSet');

        self::assertFalse($protectedProtectedSet->isPrivateSet());
        self::assertTrue($protectedPrivateSet->isPrivateSet());
    }

    /** @return list<array{0: non-empty-string, 1: bool}> */
    public static function asymmetricVisibilityImplicitFinalProvider(): array
    {
        return [
            ['publicPrivateSetIsFinal', true],
            ['protectedPrivateSetIsFinal', true],
            ['privatePrivateSetIsNotFinal', false],
        ];
    }

    /** @param non-empty-string $propertyName */
    #[DataProvider('asymmetricVisibilityImplicitFinalProvider')]
    public function testAsymmetricVisibilityImplicitFinal(string $propertyName, bool $isFinal): void
    {
        $reflector = new DefaultReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/AsymmetricVisibilityImplicitFinal.php', $this->astLocator));
        $classInfo = $reflector->reflectClass('Roave\BetterReflectionTest\Fixture\AsymmetricVisibilityImplicitFinal');
        $property  = $classInfo->getProperty($propertyName);

        self::assertSame($isFinal, $property->isFinal());
    }

    /** @return list<array{0: non-empty-string, 1: bool}> */
    public static function asymmetricVisibilityImplicitProtectedSetProvider(): array
    {
        return [
            ['publicPublicSet', false],
            ['publicProtectedSet', true],
            ['publicPrivateSet', false],
            ['protected', false],
            ['publicImplicitProtectedSet', true],
        ];
    }

    /** @param non-empty-string $propertyName */
    #[DataProvider('asymmetricVisibilityImplicitProtectedSetProvider')]
    public function testAsymmetricVisibilityImplicitProtectedSet(string $propertyName, bool $isProtectedSet): void
    {
        $reflector = new DefaultReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/AsymmetricVisibilityImplicitProtectedSet.php', $this->astLocator));
        $classInfo = $reflector->reflectClass('Roave\BetterReflectionTest\Fixture\AsymmetricVisibilityImplicitProtectedSet');
        $property  = $classInfo->getProperty($propertyName);

        self::assertSame($isProtectedSet, $property->isProtectedSet());
    }

    public function testIsAbstract(): void
    {
        $reflector = new DefaultReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/PropertyHooks.php', $this->astLocator));
        $classInfo = $reflector->reflectClass('Roave\BetterReflectionTest\Fixture\AbstractPropertyHooks');

        $hookProperty = $classInfo->getProperty('hook');
        self::assertTrue($hookProperty->isAbstract());
    }

    public function testIsAbstractInInterface(): void
    {
        $reflector = new DefaultReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/PropertyHooks.php', $this->astLocator));
        $classInfo = $reflector->reflectClass('Roave\BetterReflectionTest\Fixture\InterfaceWithProperty');

        $abstractProperty = $classInfo->getProperty('abstractPropertyFromInterface');
        self::assertTrue($abstractProperty->isAbstract());
    }

    public function testNoHooks(): void
    {
        $classInfo = $this->reflector->reflectClass(ExampleClass::class);
        $property  = $classInfo->getProperty('publicProperty');

        self::assertFalse($property->hasHooks());
        self::assertCount(0, $property->getHooks());
        self::assertFalse($property->hasHook(ReflectionPropertyHookType::Get));
        self::assertNull($property->getHook(ReflectionPropertyHookType::Set));
        self::assertFalse($property->hasHook(ReflectionPropertyHookType::Get));
        self::assertNull($property->getHook(ReflectionPropertyHookType::Set));
    }

    public function testReadOnlyHook(): void
    {
        $reflector = new DefaultReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/PropertyHooks.php', $this->astLocator));
        $classInfo = $reflector->reflectClass('Roave\BetterReflectionTest\Fixture\PropertyHooks');

        $hookProperty = $classInfo->getProperty('readOnlyHook');
        self::assertTrue($hookProperty->isDefault());
        self::assertTrue($hookProperty->isVirtual());
        self::assertTrue($hookProperty->hasHooks());

        self::assertTrue($hookProperty->hasHook(ReflectionPropertyHookType::Get));
        self::assertFalse($hookProperty->hasHook(ReflectionPropertyHookType::Set));

        $hooks = $hookProperty->getHooks();
        self::assertCount(1, $hooks);
        self::assertArrayHasKey('get', $hooks);
        self::assertInstanceOf(ReflectionMethod::class, $hooks['get']);
        self::assertSame('$readOnlyHook::get', $hooks['get']->getName());
        self::assertSame($hooks['get'], $hookProperty->getHook(ReflectionPropertyHookType::Get));
    }

    public function testWriteOnlyHook(): void
    {
        $reflector = new DefaultReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/PropertyHooks.php', $this->astLocator));
        $classInfo = $reflector->reflectClass('Roave\BetterReflectionTest\Fixture\PropertyHooks');

        $hookProperty = $classInfo->getProperty('writeOnlyHook');
        self::assertTrue($hookProperty->isDefault());
        self::assertFalse($hookProperty->isVirtual());
        self::assertTrue($hookProperty->hasHooks());

        self::assertFalse($hookProperty->hasHook(ReflectionPropertyHookType::Get));
        self::assertTrue($hookProperty->hasHook(ReflectionPropertyHookType::Set));

        $hooks = $hookProperty->getHooks();
        self::assertCount(1, $hooks);
        self::assertArrayHasKey('set', $hooks);
        self::assertInstanceOf(ReflectionMethod::class, $hooks['set']);
        self::assertSame('$writeOnlyHook::set', $hooks['set']->getName());
        self::assertSame($hooks['set'], $hookProperty->getHook(ReflectionPropertyHookType::Set));
    }

    public function testBothReadAndWriteHooks(): void
    {
        $reflector = new DefaultReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/PropertyHooks.php', $this->astLocator));
        $classInfo = $reflector->reflectClass('Roave\BetterReflectionTest\Fixture\PropertyHooks');

        $hookProperty = $classInfo->getProperty('readAndWriteHook');
        self::assertTrue($hookProperty->isDefault());
        self::assertFalse($hookProperty->isVirtual());
        self::assertTrue($hookProperty->hasHooks());

        self::assertTrue($hookProperty->hasHook(ReflectionPropertyHookType::Get));
        self::assertTrue($hookProperty->hasHook(ReflectionPropertyHookType::Set));

        $hooks = $hookProperty->getHooks();
        self::assertCount(2, $hooks);

        self::assertArrayHasKey('get', $hooks);
        self::assertInstanceOf(ReflectionMethod::class, $hooks['get']);
        self::assertSame('$readAndWriteHook::get', $hooks['get']->getName());
        self::assertSame($hooks['get'], $hookProperty->getHook(ReflectionPropertyHookType::Get));

        self::assertArrayHasKey('set', $hooks);
        self::assertInstanceOf(ReflectionMethod::class, $hooks['set']);
        self::assertSame('$readAndWriteHook::set', $hooks['set']->getName());
        self::assertSame($hooks['set'], $hookProperty->getHook(ReflectionPropertyHookType::Set));
    }

    public function testHooksForAbstractProperty(): void
    {
        $reflector = new DefaultReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/PropertyHooks.php', $this->astLocator));
        $classInfo = $reflector->reflectClass('Roave\BetterReflectionTest\Fixture\AbstractPropertyHooks');

        $hookProperty = $classInfo->getProperty('hook');

        self::assertTrue($hookProperty->isAbstract());
        self::assertTrue($hookProperty->isDefault());
        self::assertTrue($hookProperty->isVirtual());
        self::assertTrue($hookProperty->hasHooks());

        $hooks = $hookProperty->getHooks();
        self::assertCount(1, $hooks);

        self::assertArrayHasKey('get', $hooks);
        self::assertInstanceOf(ReflectionMethod::class, $hooks['get']);
        self::assertSame('$hook::get', $hooks['get']->getName());
        self::assertSame($hooks['get'], $hookProperty->getHook(ReflectionPropertyHookType::Get));
    }

    public function testHooksInInterface(): void
    {
        $reflector = new DefaultReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/PropertyHooks.php', $this->astLocator));
        $classInfo = $reflector->reflectClass('Roave\BetterReflectionTest\Fixture\InterfacePropertyHooks');

        $readOnlyHookProperty = $classInfo->getProperty('readOnlyHook');

        self::assertTrue($readOnlyHookProperty->isDefault());
        self::assertTrue($readOnlyHookProperty->isVirtual());
        self::assertTrue($readOnlyHookProperty->hasHooks());
        self::assertCount(1, $readOnlyHookProperty->getHooks());

        $writeOnlyHookProperty = $classInfo->getProperty('writeOnlyHook');

        self::assertTrue($writeOnlyHookProperty->isDefault());
        self::assertTrue($writeOnlyHookProperty->isVirtual());
        self::assertTrue($writeOnlyHookProperty->hasHooks());
        self::assertCount(1, $writeOnlyHookProperty->getHooks());

        $readAndWriteHookProperty = $classInfo->getProperty('readAndWriteHook');

        self::assertTrue($readAndWriteHookProperty->isDefault());
        self::assertTrue($readAndWriteHookProperty->isVirtual());
        self::assertTrue($readAndWriteHookProperty->hasHooks());
        self::assertCount(2, $readAndWriteHookProperty->getHooks());
    }

    /** @return list<array{0: non-empty-string, 1: bool}> */
    public static function virtualProvider(): array
    {
        return [
            ['notVirtualBecauseNoHooks', false],
            ['notVirtualBecauseThePropertyIsUsedInGet', false],
            ['notVirtualBecauseOfShortSyntax', false],
            ['virtualBecauseThePropertyIsNotUsedInGet', true],
            ['virtualBecauseSetWorksWithDifferentProperty', true],
            ['notVirtualBecauseIsPublicSoTheSetWithDifferentPropertyIsNotRelevant', false],
            ['virtualBecauseGetAndSetAbstract', true],
            ['notVirtualBecauseSetIsNotAbstract', false],
        ];
    }

    /** @param non-empty-string $propertyName */
    #[DataProvider('virtualProvider')]
    public function testVirtual(string $propertyName, bool $isVirtual): void
    {
        $reflector = new DefaultReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/PropertyHooks.php', $this->astLocator));
        $classInfo = $reflector->reflectClass('Roave\BetterReflectionTest\Fixture\ToBeVirtualOrNotToBeVirtualThatIsTheQuestion');

        $hookProperty = $classInfo->getProperty($propertyName);
        self::assertNotNull($hookProperty);
        self::assertSame($isVirtual, $hookProperty->isVirtual());
    }

    public function testExtendingHooks(): void
    {
        $reflector    = new DefaultReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/PropertyHooks.php', $this->astLocator));
        $getClassInfo = $reflector->reflectClass('Roave\BetterReflectionTest\Fixture\GetPropertyHook');

        $getHookProperty = $getClassInfo->getProperty('hook');
        self::assertCount(1, $getHookProperty->getHooks());
        self::assertTrue($getHookProperty->hasHook(ReflectionPropertyHookType::Get));
        self::assertFalse($getHookProperty->hasHook(ReflectionPropertyHookType::Set));
        self::assertSame('Roave\BetterReflectionTest\Fixture\GetPropertyHook', $getHookProperty->getHook(ReflectionPropertyHookType::Get)->getDeclaringClass()->getName());

        $getAndSetClassInfo = $reflector->reflectClass('Roave\BetterReflectionTest\Fixture\GetAndSetPropertyHook');

        $getAndSetHookProperty = $getAndSetClassInfo->getProperty('hook');
        self::assertCount(2, $getAndSetHookProperty->getHooks());
        self::assertTrue($getAndSetHookProperty->hasHook(ReflectionPropertyHookType::Get));
        self::assertTrue($getAndSetHookProperty->hasHook(ReflectionPropertyHookType::Set));
        self::assertSame('Roave\BetterReflectionTest\Fixture\GetPropertyHook', $getAndSetHookProperty->getHook(ReflectionPropertyHookType::Get)->getDeclaringClass()->getName());
        self::assertSame('Roave\BetterReflectionTest\Fixture\GetAndSetPropertyHook', $getAndSetHookProperty->getHook(ReflectionPropertyHookType::Set)->getDeclaringClass()->getName());
    }

    public function testUseHookFromTrait(): void
    {
        $reflector    = new DefaultReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/PropertyHooks.php', $this->astLocator));
        $getClassInfo = $reflector->reflectClass('Roave\BetterReflectionTest\Fixture\UsePropertyHookFromTrait');

        $hookProperty = $getClassInfo->getProperty('hook');
        self::assertCount(1, $hookProperty->getHooks());
        self::assertTrue($hookProperty->hasHook(ReflectionPropertyHookType::Get));
        self::assertFalse($hookProperty->hasHook(ReflectionPropertyHookType::Set));
        self::assertSame('Roave\BetterReflectionTest\Fixture\PropertyHookTrait', $hookProperty->getHook(ReflectionPropertyHookType::Get)->getDeclaringClass()->getName());
    }

    public function testPromotedPropertyWithHooks(): void
    {
        $reflector    = new DefaultReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/PropertyHooks.php', $this->astLocator));
        $getClassInfo = $reflector->reflectClass('Roave\BetterReflectionTest\Fixture\PromotedPropertyHooks');

        $hookProperty = $getClassInfo->getProperty('hook');
        self::assertTrue($hookProperty->isPromoted());
        self::assertCount(1, $hookProperty->getHooks());
        self::assertTrue($hookProperty->hasHook(ReflectionPropertyHookType::Get));
        self::assertFalse($hookProperty->hasHook(ReflectionPropertyHookType::Set));
    }

    public function testSetPropertyHookHasImplicitParameter(): void
    {
        $reflector    = new DefaultReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/PropertyHooks.php', $this->astLocator));
        $getClassInfo = $reflector->reflectClass('Roave\BetterReflectionTest\Fixture\SetPropertyHooksParameters');

        $hookProperty = $getClassInfo->getProperty('hookWithImplicitParameter');
        self::assertTrue($hookProperty->hasHook(ReflectionPropertyHookType::Set));

        $hookReflection           = $hookProperty->getHook(ReflectionPropertyHookType::Set);
        $hookReturnTypeReflection = $hookReflection->getReturnType();
        self::assertInstanceOf(ReflectionNamedType::class, $hookReturnTypeReflection);
        self::assertSame('void', $hookReturnTypeReflection->getName());

        self::assertSame(1, $hookReflection->getNumberOfParameters());
        self::assertInstanceOf(ReflectionParameter::class, $hookReflection->getParameter('value'));

        $hookParameterReflection = $hookReflection->getParameter('value');
        self::assertSame(0, $hookParameterReflection->getPosition());
        self::assertFalse($hookParameterReflection->isOptional());

        $hookParameterTypeReflection = $hookParameterReflection->getType();
        self::assertInstanceOf(ReflectionNamedType::class, $hookParameterTypeReflection);
        self::assertSame('string', $hookParameterTypeReflection->getName());
    }

    public function testSetPropertyHookHasExplicitParameter(): void
    {
        $reflector    = new DefaultReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/PropertyHooks.php', $this->astLocator));
        $getClassInfo = $reflector->reflectClass('Roave\BetterReflectionTest\Fixture\SetPropertyHooksParameters');

        $hookProperty = $getClassInfo->getProperty('hookWithExplicitParameter');
        self::assertTrue($hookProperty->hasHook(ReflectionPropertyHookType::Set));

        $hookReflection           = $hookProperty->getHook(ReflectionPropertyHookType::Set);
        $hookReturnTypeReflection = $hookReflection->getReturnType();
        self::assertInstanceOf(ReflectionNamedType::class, $hookReturnTypeReflection);
        self::assertSame('void', $hookReturnTypeReflection->getName());

        self::assertSame(1, $hookReflection->getNumberOfParameters());
        self::assertInstanceOf(ReflectionParameter::class, $hookReflection->getParameter('value'));

        $hookParameterReflection = $hookReflection->getParameter('value');
        self::assertSame(0, $hookParameterReflection->getPosition());
        self::assertFalse($hookParameterReflection->isOptional());

        $hookParameterTypeReflection = $hookParameterReflection->getType();
        self::assertInstanceOf(ReflectionNamedType::class, $hookParameterTypeReflection);
        self::assertSame('int', $hookParameterTypeReflection->getName());
    }

    /** @return list<array{0: non-empty-string, 1: string|null}> */
    public static function getPropertyHookReturnTypeProvider(): array
    {
        return [
            ['hookWithString', 'string'],
            ['hookWithInteger', 'int'],
            ['hookWithUnion', 'string|int'],
            ['hookWithoutType', null],
        ];
    }

    /** @param non-empty-string $propertyName */
    #[DataProvider('getPropertyHookReturnTypeProvider')]
    public function testGetPropertyHookReturnType(string $propertyName, string|null $returnType): void
    {
        $reflector = new DefaultReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/PropertyHooks.php', $this->astLocator));
        $classInfo = $reflector->reflectClass('Roave\BetterReflectionTest\Fixture\GetPropertyHooksReturnTypes');

        $hookProperty = $classInfo->getProperty($propertyName);
        self::assertNotNull($hookProperty);

        $getHookReflection = $hookProperty->getHook(ReflectionPropertyHookType::Get);
        self::assertNotNull($getHookReflection);
        self::assertSame($returnType, $getHookReflection->getReturnType()?->__toString());
    }
}
