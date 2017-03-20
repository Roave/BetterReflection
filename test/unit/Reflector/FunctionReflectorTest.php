<?php

namespace Roave\BetterReflectionTest\Reflector;

use Roave\BetterReflection\Reflection\ReflectionFunction;
use Roave\BetterReflection\Reflector\FunctionReflector;
use Roave\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;

/**
 * @covers \Roave\BetterReflection\Reflector\FunctionReflector
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

    public function testGetFunctionsFromFile()
    {
        $functions = (new FunctionReflector(
            new SingleFileSourceLocator(__DIR__ . '/../Fixture/Functions.php')
        ))->getAllFunctions();

        self::assertContainsOnlyInstancesOf(ReflectionFunction::class, $functions);
        self::assertCount(2, $functions);
    }
}
