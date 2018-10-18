<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Util\Autoload\Exception;

use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Util\Autoload\Exception\ClassAlreadyLoaded;
use function sprintf;
use function uniqid;

/**
 * @covers \Roave\BetterReflection\Util\Autoload\Exception\ClassAlreadyLoaded
 */
final class ClassAlreadyLoadedTest extends TestCase
{
    public function testFromReflectionClass() : void
    {
        $className = uniqid('class name', true);

        /** @var ReflectionClass|PHPUnit_Framework_MockObject_MockObject $reflection */
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
