<?php
declare(strict_types=1);

namespace Rector\BetterReflectionTest\Reflection\Exception;

use PHPUnit\Framework\TestCase;
use Rector\BetterReflection\Reflection\Exception\FunctionDoesNotExist;

/**
 * @covers \Rector\BetterReflection\Reflection\Exception\FunctionDoesNotExist
 */
class FunctionDoesNotExistTest extends TestCase
{
    public function testFromName() : void
    {
        $exception = FunctionDoesNotExist::fromName('boo');

        self::assertInstanceOf(FunctionDoesNotExist::class, $exception);
        self::assertSame('Function "boo" cannot be used as the function is not loaded', $exception->getMessage());
    }
}
