<?php

namespace Roave\BetterReflectionTest\Reflection\Exception;

use Roave\BetterReflection\Reflection\Exception\NotAClassReflection;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;
use Roave\BetterReflectionTest\Fixture;
use PHPUnit_Framework_TestCase;

/**
 * @covers \Roave\BetterReflection\Reflection\Exception\NotAClassReflection
 */
class NotAClassReflectionTest extends PHPUnit_Framework_TestCase
{
    public function testFromInterface()
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

    public function testFromTrait()
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
