<?php

namespace AsgrimTest;

use Asgrim\Reflector;

class ReflectionClassTest extends \PHPUnit_Framework_TestCase
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

    public function testClassNameMethods()
    {
        $classInfo = $this->reflector->reflect('\AsgrimTest\Fixture\ExampleClass');

        $this->assertTrue($classInfo->inNamespace());
        $this->assertSame('AsgrimTest\Fixture\ExampleClass', $classInfo->getName());
        $this->assertSame('AsgrimTest\Fixture', $classInfo->getNamespaceName());
        $this->assertSame('ExampleClass', $classInfo->getShortName());
    }

    public function testGetMethods()
    {
        $classInfo = $this->reflector->reflect('\AsgrimTest\Fixture\ExampleClass');
        $this->assertCount(1, $classInfo->getMethods());
    }
}
