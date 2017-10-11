<?php
declare(strict_types=1);

namespace Rector\BetterReflectionTest\Util\Autoload\ClassLoaderMethod\Exception;

use PHPUnit\Framework\TestCase;
use Rector\BetterReflection\Reflection\ReflectionClass;
use Rector\BetterReflection\Util\Autoload\ClassLoaderMethod\Exception\SignatureCheckFailed;

/**
 * @covers \Rector\BetterReflection\Util\Autoload\ClassLoaderMethod\Exception\SignatureCheckFailed
 */
final class SignatureCheckFailedTest extends TestCase
{
    public function testFromReflectionClass() : void
    {
        $className  = \uniqid('class name', true);
        $reflection = $this->createMock(ReflectionClass::class);
        $reflection->expects(self::any())->method('getName')->willReturn($className);

        $exception = SignatureCheckFailed::fromReflectionClass($reflection);

        self::assertInstanceOf(SignatureCheckFailed::class, $exception);
        self::assertSame(
            \sprintf('Failed to verify the signature of the cached file for %s', $className),
            $exception->getMessage()
        );
    }
}
