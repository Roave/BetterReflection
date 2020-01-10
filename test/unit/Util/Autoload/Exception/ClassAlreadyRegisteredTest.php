<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Util\Autoload\Exception;

use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Util\Autoload\Exception\ClassAlreadyRegistered;
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
        $reflection->expects(self::any())->method('getName')->willReturn($className);

        $exception = ClassAlreadyRegistered::fromReflectionClass($reflection);

        self::assertInstanceOf(ClassAlreadyRegistered::class, $exception);
        self::assertSame(
            sprintf('Class %s already registered', $className),
            $exception->getMessage()
        );
    }
}
