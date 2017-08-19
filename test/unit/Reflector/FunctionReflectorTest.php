<?php
declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflector;

use Roave\BetterReflection\Reflection\ReflectionFunction;
use Roave\BetterReflection\Reflector\FunctionReflector;
use Roave\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;

/**
 * @covers \Roave\BetterReflection\Reflector\FunctionReflector
 */
class FunctionReflectorTest extends \PHPUnit\Framework\TestCase
{
    public function testReflectProxiesToGenericReflectMethod() : void
    {
        $php = '<?php function foo() {}';

        $reflection = $this->createMock(ReflectionFunction::class);

        /** @var StringSourceLocator|\PHPUnit_Framework_MockObject_MockObject $sourceLocator */
        $sourceLocator = $this->getMockBuilder(StringSourceLocator::class)
            ->setConstructorArgs([$php])
            ->setMethods(['locateIdentifier'])
            ->getMock();

        $sourceLocator
            ->expects($this->once())
            ->method('locateIdentifier')
            ->will($this->returnValue($reflection));

        $reflector = new FunctionReflector($sourceLocator);
        self::assertSame($reflection, $reflector->reflect('foo'));
    }

    public function testGetFunctionsFromFile() : void
    {
        $functions = (new FunctionReflector(
            new SingleFileSourceLocator(__DIR__ . '/../Fixture/Functions.php')
        ))->getAllFunctions();

        self::assertContainsOnlyInstancesOf(ReflectionFunction::class, $functions);
        self::assertCount(2, $functions);
    }
}
