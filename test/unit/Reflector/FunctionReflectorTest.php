<?php
declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflector;

use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\Configuration;
use Roave\BetterReflection\Reflection\ReflectionFunction;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\Reflector\FunctionReflector;
use Roave\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;

/**
 * @covers \Roave\BetterReflection\Reflector\FunctionReflector
 */
class FunctionReflectorTest extends TestCase
{
    /**
     * @var ClassReflector
     */
    private $classReflector;

    protected function setUp() : void
    {
        parent::setUp();

        $this->classReflector = (new Configuration())->classReflector();
    }

    public function testReflectProxiesToGenericReflectMethod() : void
    {
        $reflection = $this->createMock(ReflectionFunction::class);

        /** @var StringSourceLocator|\PHPUnit_Framework_MockObject_MockObject $sourceLocator */
        $sourceLocator = $this
            ->getMockBuilder(StringSourceLocator::class)
            ->disableOriginalConstructor()
            ->setMethods(['locateIdentifier'])
            ->getMock();

        $sourceLocator
            ->expects($this->once())
            ->method('locateIdentifier')
            ->will($this->returnValue($reflection));

        $reflector = new FunctionReflector($sourceLocator, $this->classReflector);
        self::assertSame($reflection, $reflector->reflect('foo'));
    }

    public function testGetFunctionsFromFile() : void
    {
        $functions = (new FunctionReflector(
            new SingleFileSourceLocator(__DIR__ . '/../Fixture/Functions.php', (new Configuration())->astLocator()),
            $this->classReflector
        ))->getAllFunctions();

        self::assertContainsOnlyInstancesOf(ReflectionFunction::class, $functions);
        self::assertCount(2, $functions);
    }
}
