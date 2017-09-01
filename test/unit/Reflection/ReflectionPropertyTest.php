<?php
declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection;

use ClassWithPropertiesAndTraitProperties;
use Exception;
use ExtendedClassWithPropertiesAndTraitProperties;
use phpDocumentor\Reflection\Types;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\PropertyProperty;
use PHPUnit\Framework\TestCase;
use Reflection;
use ReflectionFunctionAbstract;
use ReflectionProperty as CoreReflectionProperty;
use Reflector;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\Exception\Uncloneable;
use Roave\BetterReflection\Reflection\ReflectionProperty;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\Type\ComposerSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;
use Roave\BetterReflectionTest\Fixture\ClassForHinting;
use Roave\BetterReflectionTest\Fixture\ExampleClass;
use TraitWithProperty;

/**
 * @covers \Roave\BetterReflection\Reflection\ReflectionProperty
 */
class ReflectionPropertyTest extends TestCase
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
     * @param string $propertyName
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
     * @param string $propertyName
     * @param string[] $expectedTypes
     * @dataProvider typesDataProvider
     */
    public function testGetDocBlockTypes(string $propertyName, array $expectedTypes) : void
    {
        $classInfo = $this->reflector->reflect(ExampleClass::class);

        $foundTypes = $classInfo->getProperty($propertyName)->getDocBlockTypes();

        self::assertCount(\count($expectedTypes), $foundTypes);

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
        $this->expectException(Exception::class);
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
     * @param string $propertyName
     * @param int $expectedModifier
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
                $classInfo,
                $classInfo,
                false
            )
            ->isDefault()
        );
    }

    public function castToStringProvider() : array
    {
        return [
            ['publicProperty', 'Property [ <default> public $publicProperty ]'],
            ['protectedProperty', 'Property [ <default> protected $protectedProperty ]'],
            ['privateProperty', 'Property [ <default> private $privateProperty ]'],
            ['publicStaticProperty', 'Property [ public static $publicStaticProperty ]'],
        ];
    }

    /**
     * @param string $propertyName
     * @param string $expectedString
     * @dataProvider castToStringProvider
     */
    public function testCastingToString(string $propertyName, string $expectedString) : void
    {
        $classInfo = $this->reflector->reflect(ExampleClass::class);
        self::assertSame($expectedString, (string) $classInfo->getProperty($propertyName));
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

    /**
     * @param string $php
     * @param int $startLine
     * @param int $endLine
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
     * @param string $php
     * @param int $startColumn
     * @param int $endColumn
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

    public function testGetAst() : void
    {
        $php = <<<'PHP'
<?php
class Foo
{
    private $test = 0;
}
PHP;

        $classReflection    = (new ClassReflector(new StringSourceLocator($php, $this->astLocator)))->reflect('Foo');
        $propertyReflection = $classReflection->getProperty('test');

        $ast = $propertyReflection->getAst();

        self::assertInstanceOf(Property::class, $ast);
        self::assertSame('test', $ast->props[0]->name);
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
}
