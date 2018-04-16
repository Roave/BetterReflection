<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection\Exception;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\Reflection\Exception\ClassDoesNotExist;
use Roave\BetterReflection\Reflection\Exception\ReflectionTypeDoesNotPointToAClassAlikeType;
use Roave\BetterReflection\Reflection\ReflectionType;

/**
 * @covers \Roave\BetterReflection\Reflection\Exception\ReflectionTypeDoesNotPointToAClassAlikeType
 */
class ReflectionTypeDoesNotPointToAClassAlikeTypeTest extends TestCase
{
    public function testFor() : void
    {
        /** @var ReflectionType|MockObject $type */
        $type = $this->createMock(ReflectionType::class);

        $type
            ->expects(self::any())
            ->method('__toString')
            ->willReturn('another potato');

        $exception = ReflectionTypeDoesNotPointToAClassAlikeType::for($type);

        self::assertInstanceOf(ReflectionTypeDoesNotPointToAClassAlikeType::class, $exception);
        self::assertSame('The reflected type "potato" is not a class', $exception->getMessage());
    }
}
