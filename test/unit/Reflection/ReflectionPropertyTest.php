<?php

namespace BetterReflectionTest\Reflection;

use BetterReflection\Reflection\ReflectionProperty;
use BetterReflection\Reflector\ClassReflector;
use BetterReflection\SourceLocator\ComposerSourceLocator;

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
    public function typesDataProvider()
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
     * @dataProvider typesDataProvider
     */
    public function testGetDocBlockTypeStrings($propertyName, $expectedTypes)
    {
        $classInfo = $this->reflector->reflect('\BetterReflectionTest\Fixture\ExampleClass');

        $property = $classInfo->getProperty($propertyName);

        $this->assertSame($expectedTypes, $property->getDocBlockTypeStrings());
    }

    public function testGetDocComment()
    {
        $expectedDoc = "/**\n * @var string\n */";

        $classInfo = $this->reflector->reflect('\BetterReflectionTest\Fixture\ExampleClass');
        $property = $classInfo->getProperty('publicProperty');

        $this->assertSame($expectedDoc, $property->getDocComment());
    }

    public function testExportThrowsException()
    {
        $this->setExpectedException(\Exception::class);
        ReflectionProperty::export();
    }

    public function modifierProvider()
    {
        return [
            ['publicProperty', 256, ['public']],
            ['protectedProperty', 512, ['protected']],
            ['privateProperty', 1024, ['private']],
            ['publicStaticProperty', 257, ['public', 'static']],
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
}
