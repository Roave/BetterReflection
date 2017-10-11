<?php
declare(strict_types=1);

namespace Rector\BetterReflectionTest\Reflector;

use PHPUnit\Framework\TestCase;
use Rector\BetterReflection\Reflection\ReflectionFunction;
use Rector\BetterReflection\Reflector\ClassReflector;
use Rector\BetterReflection\Reflector\FunctionReflector;
use Rector\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;
use Rector\BetterReflection\SourceLocator\Type\StringSourceLocator;
use Rector\BetterReflectionTest\BetterReflectionSingleton;

/**
 * @covers \Rector\BetterReflection\Reflector\FunctionReflector
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

        $this->classReflector = BetterReflectionSingleton::instance()->classReflector();
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
            new SingleFileSourceLocator(__DIR__ . '/../Fixture/Functions.php', BetterReflectionSingleton::instance()->astLocator()),
            $this->classReflector
        ))->getAllFunctions();

        self::assertContainsOnlyInstancesOf(ReflectionFunction::class, $functions);
        self::assertCount(2, $functions);
    }
}
