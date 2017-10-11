<?php
declare(strict_types=1);

namespace Rector\BetterReflectionTest\Util\Autoload\Exception;

use PHPUnit\Framework\TestCase;
use Rector\BetterReflection\Reflection\ReflectionClass;
use Rector\BetterReflection\Util\Autoload\Exception\ClassAlreadyLoaded;

/**
 * @covers \Rector\BetterReflection\Util\Autoload\Exception\ClassAlreadyLoaded
 */
final class ClassAlreadyLoadedTest extends TestCase
{
    public function testFromReflectionClass() : void
    {
        $className = \uniqid('class name', true);

        /** @var ReflectionClass|\PHPUnit_Framework_MockObject_MockObject $reflection */
        $reflection = $this->createMock(ReflectionClass::class);
        $reflection->expects(self::any())->method('getName')->willReturn($className);

        $exception = ClassAlreadyLoaded::fromReflectionClass($reflection);

        self::assertInstanceOf(ClassAlreadyLoaded::class, $exception);
        self::assertSame(
            \sprintf('Class %s has already been loaded into memory so cannot be modified', $className),
            $exception->getMessage()
        );
    }
}
