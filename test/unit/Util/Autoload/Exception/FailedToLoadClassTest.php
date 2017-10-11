<?php
declare(strict_types=1);

namespace Rector\BetterReflectionTest\Util\Autoload\Exception;

use PHPUnit\Framework\TestCase;
use Rector\BetterReflection\Util\Autoload\Exception\FailedToLoadClass;

/**
 * @covers \Rector\BetterReflection\Util\Autoload\Exception\FailedToLoadClass
 */
final class FailedToLoadClassTest extends TestCase
{
    public function testFromReflectionClass() : void
    {
        $className = \uniqid('class name', true);

        $exception = FailedToLoadClass::fromClassName($className);

        self::assertInstanceOf(FailedToLoadClass::class, $exception);
        self::assertSame(
            \sprintf('Unable to load class %s', $className),
            $exception->getMessage()
        );
    }
}
