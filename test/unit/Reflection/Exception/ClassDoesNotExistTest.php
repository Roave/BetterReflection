<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection\Exception;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\Reflection\Exception\ClassDoesNotExist;
use Roave\BetterReflection\Reflection\Reflection;
use function assert;

/**
 * @covers \Roave\BetterReflection\Reflection\Exception\ClassDoesNotExist
 */
class ClassDoesNotExistTest extends TestCase
{
    public function testForDifferentReflectionType() : void
    {
        $reflection = $this->createMock(Reflection::class);
        assert($reflection instanceof Reflection || $reflection instanceof MockObject);

        $reflection
            ->expects(self::any())
            ->method('getName')
            ->willReturn('potato');

        $exception = ClassDoesNotExist::forDifferentReflectionType($reflection);

        self::assertInstanceOf(ClassDoesNotExist::class, $exception);
        self::assertSame('The reflected type "potato" is not a class', $exception->getMessage());
    }
}
