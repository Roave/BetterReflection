<?php

namespace Roave\BetterReflectionTest\Util\Autoload\Exception;

use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Util\Autoload\Exception\ClassAlreadyLoaded;

/**
 * @covers \Roave\BetterReflection\Util\Autoload\Exception\ClassAlreadyLoaded
 */
class ClassAlreadyLoadedTest extends \PHPUnit_Framework_TestCase
{
    public function testFromReflectionClass()
    {
        $className = uniqid('class name', true);

        /** @var ReflectionClass|\PHPUnit_Framework_MockObject_MockObject $reflection */
        $reflection = $this->createMock(ReflectionClass::class);
        $reflection->expects(self::any())->method('getName')->willReturn($className);

        $exception = ClassAlreadyLoaded::fromReflectionClass($reflection);

        self::assertInstanceOf(ClassAlreadyLoaded::class, $exception);
        self::assertSame(
            sprintf('Class %s has already been loaded into memory so cannot be modified', $className),
            $exception->getMessage()
        );
    }
}
