<?php

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
    public function testFromClass()
    {
        $sourceLocator = new SingleFileSourceLocator(__DIR__ . '/../../Fixture/ExampleClass.php');
        $reflector     = new ClassReflector($sourceLocator);

        $exception = NotAnInterfaceReflection::fromReflectionClass($reflector->reflect(Fixture\ExampleClass::class));

        $this->assertInstanceOf(NotAnInterfaceReflection::class, $exception);
        $this->assertSame(
            'Provided node "' . Fixture\ExampleClass::class . '" is not interface, but "class"',
            $exception->getMessage()
        );
    }

    public function testFromTrait()
    {
        $sourceLocator = new SingleFileSourceLocator(__DIR__ . '/../../Fixture/ExampleClass.php');
        $reflector     = new ClassReflector($sourceLocator);

        $exception = NotAnInterfaceReflection::fromReflectionClass($reflector->reflect(Fixture\ExampleTrait::class));

        $this->assertInstanceOf(NotAnInterfaceReflection::class, $exception);
        $this->assertSame(
            'Provided node "' . Fixture\ExampleTrait::class . '" is not interface, but "trait"',
            $exception->getMessage()
        );
    }
}
