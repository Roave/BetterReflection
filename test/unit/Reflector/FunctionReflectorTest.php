<?php

namespace BetterReflectionTest\Reflector;

use BetterReflection\Reflector\FunctionReflector;
use BetterReflection\SourceLocator\Type\StringSourceLocator;

/**
 * @covers \BetterReflection\Reflector\FunctionReflector
 */
class FunctionReflectorTest extends \PHPUnit_Framework_TestCase
{
    public function testReflectProxiesToGenericReflectMethod()
    {
        $php = '<?php function foo() {}';

        /** @var StringSourceLocator|\PHPUnit_Framework_MockObject_MockObject $sourceLocator */
        $sourceLocator = $this->getMockBuilder(StringSourceLocator::class)
            ->setConstructorArgs([$php])
            ->setMethods(['locateIdentifier'])
            ->getMock();

        $sourceLocator
            ->expects($this->once())
            ->method('locateIdentifier')
            ->will($this->returnValue('foobar'));

        $reflector = new FunctionReflector($sourceLocator);
        $this->assertSame('foobar', $reflector->reflect('foo'));
    }
}
