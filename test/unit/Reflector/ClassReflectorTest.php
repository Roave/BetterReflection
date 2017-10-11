<?php
declare(strict_types=1);

namespace Rector\BetterReflectionTest\Reflector;

use PHPUnit\Framework\TestCase;
use Rector\BetterReflection\Reflection\ReflectionClass;
use Rector\BetterReflection\Reflector\ClassReflector;
use Rector\BetterReflection\Reflector\Exception\IdentifierNotFound;
use Rector\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;
use Rector\BetterReflection\SourceLocator\Type\SourceLocator;
use Rector\BetterReflection\SourceLocator\Type\StringSourceLocator;
use Rector\BetterReflectionTest\BetterReflectionSingleton;

/**
 * @covers \Rector\BetterReflection\Reflector\ClassReflector
 */
class ClassReflectorTest extends TestCase
{
    public function testGetClassesFromFile() : void
    {
        $classes = (new ClassReflector(
            new SingleFileSourceLocator(__DIR__ . '/../Fixture/ExampleClass.php', BetterReflectionSingleton::instance()->astLocator())
        ))->getAllClasses();

        self::assertContainsOnlyInstancesOf(ReflectionClass::class, $classes);
        self::assertCount(9, $classes);
    }

    public function testReflectProxiesToSourceLocator() : void
    {
        $reflection = $this->createMock(ReflectionClass::class);

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

        $reflector = new ClassReflector($sourceLocator);

        self::assertSame($reflection, $reflector->reflect('MyClass'));
    }

    public function testThrowsExceptionWhenIdentifierNotFound() : void
    {
        $defaultReflector = BetterReflectionSingleton::instance()->classReflector();

        $this->expectException(IdentifierNotFound::class);

        $defaultReflector->reflect('Something\That\Should\Not\Exist');
    }
}
