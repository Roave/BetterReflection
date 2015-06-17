<?php

namespace AsgrimTest;

use Asgrim\Reflector;

class ReflectionPropertyTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Reflector
     */
    private $reflector;

    public function setUp()
    {
        global $loader;
        $this->reflector = new Reflector($loader);
    }

    public function testVisibilityMethods()
    {
        $classInfo = $this->reflector->reflect('\AsgrimTest\Fixture\ExampleClass');

        $privateProp = $classInfo->getProperty('privateProperty');
        $this->assertTrue($privateProp->isPrivate());

        $protectedProp = $classInfo->getProperty('protectedProperty');
        $this->assertTrue($protectedProp->isProtected());

        $publicProp = $classInfo->getProperty('publicProperty');
        $this->assertTrue($publicProp->isPublic());
    }

    public function testIsStatic()
    {
        $classInfo = $this->reflector->reflect('\AsgrimTest\Fixture\ExampleClass');

        $publicProp = $classInfo->getProperty('publicProperty');
        $this->assertFalse($publicProp->isStatic());

        $staticProp = $classInfo->getProperty('publicStaticProperty');
        $this->assertTrue($staticProp->isStatic());
    }
}