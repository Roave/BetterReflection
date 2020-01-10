<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Util\Autoload\Exception;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Util\Autoload\Exception\ClassAlreadyRegistered;
use function assert;
use function sprintf;
use function uniqid;

/**
 * @covers \Roave\BetterReflection\Util\Autoload\Exception\ClassAlreadyRegistered
 */
final class ClassAlreadyRegisteredTest extends TestCase
{
    public function testFromReflectionClass() : void
    {
        $className = uniqid('class name', true);

        $reflection = $this->createMock(ReflectionClass::class);
        assert($reflection instanceof ReflectionClass || $reflection instanceof MockObject);
        $reflection->expects(self::any())->method('getName')->willReturn($className);

        $exception = ClassAlreadyRegistered::fromReflectionClass($reflection);

        self::assertInstanceOf(ClassAlreadyRegistered::class, $exception);
        self::assertSame(
            sprintf('Class %s already registered', $className),
            $exception->getMessage()
        );
    }
}
