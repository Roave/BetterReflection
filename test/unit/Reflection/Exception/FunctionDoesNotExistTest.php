<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\Reflection\Exception\FunctionDoesNotExist;

#[CoversClass(FunctionDoesNotExist::class)]
class FunctionDoesNotExistTest extends TestCase
{
    public function testFromName(): void
    {
        $exception = FunctionDoesNotExist::fromName('boo');

        self::assertInstanceOf(FunctionDoesNotExist::class, $exception);
        self::assertSame('Function "boo" cannot be used as the function is not loaded', $exception->getMessage());
    }
}
