<?php
declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection\Exception;

use Roave\BetterReflection\Reflection\Exception\Uncloneable;
use PHPUnit_Framework_TestCase;

/**
 * @covers \Roave\BetterReflection\Reflection\Exception\Uncloneable
 */
class UncloneableTest extends PHPUnit_Framework_TestCase
{
    public function testFromNonObject() : void
    {
        $exception = Uncloneable::fromClass('foo');

        self::assertInstanceOf(Uncloneable::class, $exception);
        self::assertSame('Trying to clone an uncloneable object of class foo', $exception->getMessage());
    }
}
