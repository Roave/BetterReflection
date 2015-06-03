<?php

namespace AsgrimTest;

use Asgrim\Investigator;

class ReflectionClassTest extends \PHPUnit_Framework_TestCase
{
    private $investigator;

    public function setUp()
    {
        global $loader;
        $this->investigator = new Investigator($loader);
    }

    public function testGetMethods()
    {
        $classInfo = $this->investigator->investigate('\AsgrimTest\Fixture\ExampleClass');
        $this->assertCount(1, $classInfo->getMethods());
    }

    public function testHasMethod()
    {
        $classInfo = $this->investigator->investigate('\AsgrimTest\Fixture\ExampleClass');
        $this->assertTrue($classInfo->hasMethod('someMethod'));
    }
}
