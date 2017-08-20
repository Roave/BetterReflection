<?php
declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection\Exception;

use Roave\BetterReflection\Reflection\Exception\NotAClassReflection;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;
use Roave\BetterReflectionTest\Fixture;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Roave\BetterReflection\Reflection\Exception\NotAClassReflection
 */
class NotAClassReflectionTest extends TestCase
{
    public function testFromInterface() : void
    {
        $sourceLocator = new SingleFileSourceLocator(__DIR__ . '/../../Fixture/ExampleClass.php');
        $reflector     = new ClassReflector($sourceLocator);

        $exception = NotAClassReflection::fromReflectionClass($reflector->reflect(Fixture\ExampleInterface::class));

        self::assertInstanceOf(NotAClassReflection::class, $exception);
        self::assertSame(
            'Provided node "' . Fixture\ExampleInterface::class . '" is not class, but "interface"',
            $exception->getMessage()
        );
    }

    public function testFromTrait() : void
    {
        $sourceLocator = new SingleFileSourceLocator(__DIR__ . '/../../Fixture/ExampleClass.php');
        $reflector     = new ClassReflector($sourceLocator);

        $exception = NotAClassReflection::fromReflectionClass($reflector->reflect(Fixture\ExampleTrait::class));

        self::assertInstanceOf(NotAClassReflection::class, $exception);
        self::assertSame(
            'Provided node "' . Fixture\ExampleTrait::class . '" is not class, but "trait"',
            $exception->getMessage()
        );
    }
}
