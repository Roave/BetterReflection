<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Util\Autoload\Exception;

use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\Util\Autoload\Exception\FailedToLoadClass;
use function sprintf;
use function uniqid;

/**
 * @covers \Roave\BetterReflection\Util\Autoload\Exception\FailedToLoadClass
 */
final class FailedToLoadClassTest extends TestCase
{
    public function testFromReflectionClass() : void
    {
        $className = uniqid('class name', true);

        $exception = FailedToLoadClass::fromClassName($className);

        self::assertInstanceOf(FailedToLoadClass::class, $exception);
        self::assertSame(
            sprintf('Unable to load class %s', $className),
            $exception->getMessage(),
        );
    }
}
