<?php
declare(strict_types=1);

namespace Rector\BetterReflectionTest\Reflection\Exception;

use PHPUnit\Framework\TestCase;
use Rector\BetterReflection\Reflection\Exception\Uncloneable;

/**
 * @covers \Rector\BetterReflection\Reflection\Exception\Uncloneable
 */
class UncloneableTest extends TestCase
{
    public function testFromNonObject() : void
    {
        $exception = Uncloneable::fromClass('foo');

        self::assertInstanceOf(Uncloneable::class, $exception);
        self::assertSame('Trying to clone an uncloneable object of class foo', $exception->getMessage());
    }
}
