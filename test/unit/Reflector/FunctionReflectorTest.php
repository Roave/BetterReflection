<?php

namespace BetterReflectionTest\Reflector;

use BetterReflection\Reflection\ReflectionFunction;
use BetterReflection\Reflector\FunctionReflector;
use BetterReflection\Reflector\Generic;
use BetterReflection\SourceLocator\StringSourceLocator;

/**
 * @covers \BetterReflection\Reflector\FunctionReflector
 */
class FunctionReflectorTest extends \PHPUnit_Framework_TestCase
{
    public function testReflectProxiesToGenericReflectMethod()
    {
        $php = '<?php function foo() {}';

        $reflector = new FunctionReflector(new StringSourceLocator($php));

        $reflectionMock = $this->getMockBuilder(ReflectionFunction::class)
            ->disableOriginalConstructor()
            ->getMock();

        $genericReflectorMock = $this->getMockBuilder(Generic::class)
            ->setMethods(['reflect'])
            ->disableOriginalConstructor()
            ->getMock();

        $genericReflectorMock->expects($this->once())
            ->method('reflect')
            ->will($this->returnValue($reflectionMock));

        $reflectorReflection = new \ReflectionObject($reflector);
        $reflectorReflectorReflection = $reflectorReflection->getProperty('reflector');
        $reflectorReflectorReflection->setAccessible(true);
        $reflectorReflectorReflection->setValue($reflector, $genericReflectorMock);

        $reflector->reflect('foo');
    }
}
