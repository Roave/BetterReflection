<?php

namespace BetterReflectionTest\Reflection;

use BetterReflection\Reflector\ClassReflector;
use BetterReflection\SourceLocator\ComposerSourceLocator;

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

    /**
     * @covers \BetterReflection\Reflection\ReflectionProperty::isPublic()
     * @covers \BetterReflection\Reflection\ReflectionProperty::isProtected()
     * @covers \BetterReflection\Reflection\ReflectionProperty::isPrivate()
     */
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

    /**
     * @covers \BetterReflection\Reflection\ReflectionProperty::isStatic()
     */
    public function testIsStatic()
    {
        $classInfo = $this->reflector->reflect('\BetterReflectionTest\Fixture\ExampleClass');

        $publicProp = $classInfo->getProperty('publicProperty');
        $this->assertFalse($publicProp->isStatic());

        $staticProp = $classInfo->getProperty('publicStaticProperty');
        $this->assertTrue($staticProp->isStatic());
    }

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
     * @covers \BetterReflection\Reflection\ReflectionProperty::getTypeStrings()
     */
    public function testGetDocBlockTypeStrings($propertyName, $expectedTypes)
    {
        $classInfo = $this->reflector->reflect('\BetterReflectionTest\Fixture\ExampleClass');

        $property = $classInfo->getProperty($propertyName);

        $this->assertSame($expectedTypes, $property->getDocBlockTypeStrings());
    }
}
