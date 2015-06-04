<?php

namespace AsgrimTest;

use Asgrim\Investigator;

class ReflectionClassTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Investigator
     */
    private $investigator;

    public function setUp()
    {
        global $loader;
        $this->investigator = new Investigator($loader);
    }

    public function testClassNameMethods()
    {
        $classInfo = $this->investigator->investigate('\AsgrimTest\Fixture\ExampleClass');

        $this->assertTrue($classInfo->inNamespace());
        $this->assertSame('AsgrimTest\Fixture\ExampleClass', $classInfo->getName());
        $this->assertSame('AsgrimTest\Fixture', $classInfo->getNamespaceName());
        $this->assertSame('ExampleClass', $classInfo->getShortName());
    }

    public function testGetMethods()
    {
        $classInfo = $this->investigator->investigate('\AsgrimTest\Fixture\ExampleClass');
        $this->assertCount(1, $classInfo->getMethods());
    }
}
