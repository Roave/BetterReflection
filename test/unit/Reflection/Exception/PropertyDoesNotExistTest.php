<?php
declare(strict_types=1);

namespace Rector\BetterReflectionTest\Reflection\Exception;

use PHPUnit\Framework\TestCase;
use Rector\BetterReflection\Reflection\Exception\PropertyDoesNotExist;

/**
 * @covers \Rector\BetterReflection\Reflection\Exception\PropertyDoesNotExist
 */
class PropertyDoesNotExistTest extends TestCase
{
    public function testFromName() : void
    {
        $exception = PropertyDoesNotExist::fromName('boo');

        self::assertInstanceOf(PropertyDoesNotExist::class, $exception);
        self::assertSame('Property "boo" does not exist', $exception->getMessage());
    }
}
