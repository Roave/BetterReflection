<?php
declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection\Exception;

use Roave\BetterReflection\Reflection\Exception\NotAnInterfaceReflection;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;
use Roave\BetterReflectionTest\Fixture;
use PHPUnit_Framework_TestCase;

/**
 * @covers \Roave\BetterReflection\Reflection\Exception\NotAnInterfaceReflection
 */
class NotAnInterfaceReflectionTest extends PHPUnit_Framework_TestCase
{
    public function testFromClass() : void
    {
        $sourceLocator = new SingleFileSourceLocator(__DIR__ . '/../../Fixture/ExampleClass.php');
        $reflector     = new ClassReflector($sourceLocator);

        $exception = NotAnInterfaceReflection::fromReflectionClass($reflector->reflect(Fixture\ExampleClass::class));

        self::assertInstanceOf(NotAnInterfaceReflection::class, $exception);
        self::assertSame(
            'Provided node "' . Fixture\ExampleClass::class . '" is not interface, but "class"',
            $exception->getMessage()
        );
    }

    public function testFromTrait() : void
    {
        $sourceLocator = new SingleFileSourceLocator(__DIR__ . '/../../Fixture/ExampleClass.php');
        $reflector     = new ClassReflector($sourceLocator);

        $exception = NotAnInterfaceReflection::fromReflectionClass($reflector->reflect(Fixture\ExampleTrait::class));

        self::assertInstanceOf(NotAnInterfaceReflection::class, $exception);
        self::assertSame(
            'Provided node "' . Fixture\ExampleTrait::class . '" is not interface, but "trait"',
            $exception->getMessage()
        );
    }
}
