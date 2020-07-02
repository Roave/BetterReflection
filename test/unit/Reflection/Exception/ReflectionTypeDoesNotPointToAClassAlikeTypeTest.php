<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection\Exception;

use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\Reflection\Exception\ReflectionTypeDoesNotPointToAClassAlikeType;
use Roave\BetterReflection\Reflection\ReflectionType;

/**
 * @covers \Roave\BetterReflection\Reflection\Exception\ReflectionTypeDoesNotPointToAClassAlikeType
 */
class ReflectionTypeDoesNotPointToAClassAlikeTypeTest extends TestCase
{
    public function testFor(): void
    {
        $type = $this->createMock(ReflectionType::class);

        $type
            ->expects(self::any())
            ->method('__toString')
            ->willReturn('another potato');

        $exception = ReflectionTypeDoesNotPointToAClassAlikeType::for($type);

        self::assertInstanceOf(ReflectionTypeDoesNotPointToAClassAlikeType::class, $exception);
        self::assertStringMatchesFormat(
            'Provided %s instance does not point to a class-alike type, but to "another potato"',
            $exception->getMessage(),
        );
    }
}
