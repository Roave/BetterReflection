<?php
declare(strict_types=1);

namespace Rector\BetterReflectionTest\Util\Autoload\Exception;

use PHPUnit\Framework\TestCase;
use Rector\BetterReflection\Reflection\ReflectionClass;
use Rector\BetterReflection\Util\Autoload\Exception\ClassAlreadyRegistered;

/**
 * @covers \Rector\BetterReflection\Util\Autoload\Exception\ClassAlreadyRegistered
 */
final class ClassAlreadyRegisteredTest extends TestCase
{
    public function testFromReflectionClass() : void
    {
        $className = \uniqid('class name', true);

        /** @var ReflectionClass|\PHPUnit_Framework_MockObject_MockObject $reflection */
        $reflection = $this->createMock(ReflectionClass::class);
        $reflection->expects(self::any())->method('getName')->willReturn($className);

        $exception = ClassAlreadyRegistered::fromReflectionClass($reflection);

        self::assertInstanceOf(ClassAlreadyRegistered::class, $exception);
        self::assertSame(
            \sprintf('Class %s already registered', $className),
            $exception->getMessage()
        );
    }
}
