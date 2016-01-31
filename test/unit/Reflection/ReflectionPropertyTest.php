<?php

namespace BetterReflectionTest\Reflection;

use BetterReflection\Reflection\Adapter\ReflectionClass;
use BetterReflection\Reflection\Exception\Uncloneable;
use BetterReflection\Reflection\ReflectionProperty;
use BetterReflection\Reflector\ClassReflector;
use BetterReflection\SourceLocator\Type\ComposerSourceLocator;
use BetterReflection\SourceLocator\Type\SingleFileSourceLocator;
use BetterReflectionTest\Fixture\ClassForHinting;
use phpDocumentor\Reflection\Types;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\PropertyProperty;

/**
 * @covers \BetterReflection\Reflection\ReflectionProperty
 */
class ReflectionPropertyTest extends \PHPUnit_Framework_TestCase
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

    public function testCreateFromName()
    {
        $property = ReflectionProperty::createFromName(\ReflectionFunctionAbstract::class, 'name');

        $this->assertInstanceOf(ReflectionProperty::class, $property);
        $this->assertSame('name', $property->getName());
    }

    public function testCreateFromInstance()
    {
        $property = ReflectionProperty::createFromInstance(new ClassForHinting(), 'someProperty');

        $this->assertInstanceOf(ReflectionProperty::class, $property);
        $this->assertSame('someProperty', $property->getName());
    }

    public function testImplementsReflector()
    {
        $classInfo = $this->reflector->reflect('\BetterReflectionTest\Fixture\ExampleClass');
        $publicProp = $classInfo->getProperty('publicProperty');

        $this->assertInstanceOf(\Reflector::class, $publicProp);
    }

    public function testVisibilityMethods()
    {
        $classInfo = $this->reflector->reflect('\BetterReflectionTest\Fixture\ExampleClass');

        $privateProp = $classInfo->getProperty('privateProperty');
        $this->assertTrue($privateProp->isPrivate());

        $protectedProp = $classInfo->getProperty('protectedProperty');
        $this->assertTrue($protectedProp->isProtected());

        $publicProp = $classInfo->getProperty('publicProperty');
        $this->assertTrue($publicProp->isPublic());
    }

    public function testIsStatic()
    {
        $classInfo = $this->reflector->reflect('\BetterReflectionTest\Fixture\ExampleClass');

        $publicProp = $classInfo->getProperty('publicProperty');
        $this->assertFalse($publicProp->isStatic());

        $staticProp = $classInfo->getProperty('publicStaticProperty');
        $this->assertTrue($staticProp->isStatic());
    }

    /**
     * @return array
     */
    public function stringTypesDataProvider()
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
    public function testGetDocBlockTypeStrings($propertyName, $expectedTypes)
    {
        $classInfo = $this->reflector->reflect('\BetterReflectionTest\Fixture\ExampleClass');

        $property = $classInfo->getProperty($propertyName);

        $this->assertSame($expectedTypes, $property->getDocBlockTypeStrings());
    }

    /**
     * @return array
     */
    public function typesDataProvider()
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
    public function testGetDocBlockTypes($propertyName, $expectedTypes)
    {
        $classInfo = $this->reflector->reflect('\BetterReflectionTest\Fixture\ExampleClass');

        $foundTypes = $classInfo->getProperty($propertyName)->getDocBlockTypes();

        $this->assertCount(count($expectedTypes), $foundTypes);

        foreach ($expectedTypes as $i => $expectedType) {
            $this->assertInstanceOf($expectedType, $foundTypes[$i]);
        }
    }

    public function testGetDocComment()
    {
        $expectedDoc = "/**\n * @var string\n */";

        $classInfo = $this->reflector->reflect('\BetterReflectionTest\Fixture\ExampleClass');
        $property = $classInfo->getProperty('publicProperty');

        $this->assertSame($expectedDoc, $property->getDocComment());
    }

    public function testGetDocCommentReturnsEmptyStringWithNoComment()
    {
        $classInfo = $this->reflector->reflect('\BetterReflectionTest\Fixture\ExampleClass');
        $property = $classInfo->getProperty('publicStaticProperty');

        $this->assertSame('', $property->getDocComment());
    }

    public function testExportThrowsException()
    {
        $this->setExpectedException(\Exception::class);
        ReflectionProperty::export();
    }

    public function modifierProvider()
    {
        return [
            ['publicProperty', \ReflectionProperty::IS_PUBLIC, ['public']],
            ['protectedProperty', \ReflectionProperty::IS_PROTECTED, ['protected']],
            ['privateProperty', \ReflectionProperty::IS_PRIVATE, ['private']],
            ['publicStaticProperty', \ReflectionProperty::IS_PUBLIC | \ReflectionProperty::IS_STATIC, ['public', 'static']],
        ];
    }

    /**
     * @param string $propertyName
     * @param int $expectedModifier
     * @param string[] $expectedModifierNames
     * @dataProvider modifierProvider
     */
    public function testGetModifiers($propertyName, $expectedModifier, array $expectedModifierNames)
    {
        $classInfo = $this->reflector->reflect('\BetterReflectionTest\Fixture\ExampleClass');
        $property = $classInfo->getProperty($propertyName);

        $this->assertSame($expectedModifier, $property->getModifiers());
        $this->assertSame(
            $expectedModifierNames,
            \Reflection::getModifierNames($property->getModifiers())
        );
    }

    public function testIsDefault()
    {
        $classInfo = $this->reflector->reflect('\BetterReflectionTest\Fixture\ExampleClass');

        $this->assertTrue($classInfo->getProperty('publicProperty')->isDefault());
        $this->assertTrue($classInfo->getProperty('publicStaticProperty')->isDefault());
    }

    public function testIsDefaultWithRuntimeDeclaredProperty()
    {
        $this->assertFalse(
            ReflectionProperty::createFromNode(
                $this->reflector,
                new Property(Class_::MODIFIER_PUBLIC, [new PropertyProperty('foo')]),
                $this->reflector->reflect('\BetterReflectionTest\Fixture\ExampleClass'),
                false
            )
            ->isDefault()
        );
    }

    public function castToStringProvider()
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
    public function testCastingToString($propertyName, $expectedString)
    {
        $classInfo = $this->reflector->reflect('\BetterReflectionTest\Fixture\ExampleClass');
        $this->assertSame($expectedString, (string)$classInfo->getProperty($propertyName));
    }

    public function testGetDefaultProperty()
    {
        $classInfo = (new ClassReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/DefaultProperties.php')))->reflect('Foo');

        $this->assertSame(123, $classInfo->getProperty('hasDefault')->getDefaultValue());
        $this->assertNull($classInfo->getProperty('noDefault')->getDefaultValue());
    }

    public function testCannotClone()
    {
        $classInfo = $this->reflector->reflect('\BetterReflectionTest\Fixture\ExampleClass');
        $publicProp = $classInfo->getProperty('publicProperty');

        $this->setExpectedException(Uncloneable::class);
        $unused = clone $publicProp;
    }
}
