<?php

namespace AsgrimTest;

use Asgrim\Reflector;
use Asgrim\ReflectionParameter;

class ReflectionMethodTest extends \PHPUnit_Framework_TestCase
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

    public function visibilityProvider()
    {
        return [
            'publicMethod' => ['publicMethod', true, false, false, false, false, false],
            'privateMethod' => ['privateMethod', false, true, false, false, false, false],
            'protectedMethod' => ['protectedMethod', false, false, true, false, false, false],
            'finalPublicMethod' => ['finalPublicMethod', true, false, false, true, false, false],
            'abstractPublicMethod' => ['abstractPublicMethod', true, false, false, false, true, false],
            'staticPublicMethod' => ['staticPublicMethod', true, false, false, false, false, true],
            'noVisibility' => ['publicMethod', true, false, false, false, false, false],
        ];
    }

    /**
     * @dataProvider visibilityProvider
     */
    public function testVisibilityOfMethods($method, $shouldBePublic, $shouldBePrivate, $shouldBeProtected, $shouldBeFinal, $shouldBeAbstract, $shouldBeStatic)
    {
        $classInfo = $this->reflector->reflect('\AsgrimTest\Fixture\MethodsTest');
        $method = $classInfo->getMethod($method);

        $this->assertSame($shouldBePublic, $method->isPublic());
        $this->assertSame($shouldBePrivate, $method->isPrivate());
        $this->assertSame($shouldBeProtected, $method->isProtected());
        $this->assertSame($shouldBeFinal, $method->isFinal());
        $this->assertSame($shouldBeAbstract, $method->isAbstract());
        $this->assertSame($shouldBeStatic, $method->isStatic());
    }

    public function testIsConstructorDestructor()
    {
        $classInfo = $this->reflector->reflect('\AsgrimTest\Fixture\MethodsTest');

        $method = $classInfo->getMethod('__construct');
        $this->assertTrue($method->isConstructor());

        $method = $classInfo->getMethod('__destruct');
        $this->assertTrue($method->isDestructor());
    }

    public function testGetParameters()
    {
        $classInfo = $this->reflector->reflect('\AsgrimTest\Fixture\MethodsTest');

        $method = $classInfo->getMethod('methodWithParameters');
        $params = $method->getParameters();

        $this->assertCount(2, $params);
        $this->assertContainsOnlyInstancesOf(ReflectionParameter::class, $params);

        $this->assertSame('parameter1', $params[0]->getName());
        $this->assertSame('parameter2', $params[1]->getName());
    }

    public function testGetNumberOfParameters()
    {
        $classInfo = $this->reflector->reflect('\AsgrimTest\Fixture\MethodsTest');

        $method1 = $classInfo->getMethod('methodWithParameters');
        $this->assertSame(2, $method1->getNumberOfParameters(), 'Failed asserting methodWithParameters has 2 params');

        $method2 = $classInfo->getMethod('methodWithOptionalParameters');
        $this->assertSame(2, $method2->getNumberOfParameters(), 'Failed asserting methodWithOptionalParameters has 2 params');
    }
}
