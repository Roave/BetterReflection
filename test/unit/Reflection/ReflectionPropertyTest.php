<?php

namespace BetterReflectionTest\Reflection;

use BetterReflection\Reflection\ReflectionProperty;
use BetterReflection\Reflector\ClassReflector;
use BetterReflection\SourceLocator\ComposerSourceLocator;
use phpDocumentor\Reflection\Types;

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
        $this->assertFalse($classInfo->getProperty('publicStaticProperty')->isDefault());
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
}
