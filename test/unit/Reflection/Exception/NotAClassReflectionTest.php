<?php

namespace BetterReflectionTest\Reflection\Exception;

use BetterReflection\Reflection\Exception\NotAClassReflection;
use BetterReflection\Reflector\ClassReflector;
use BetterReflection\SourceLocator\SingleFileSourceLocator;
use BetterReflectionTest\ClassWithInterfaces;
use BetterReflectionTest\ClassWithInterfacesOther;
use BetterReflectionTest\Fixture;
use PHPUnit_Framework_TestCase;

/**
 * @covers \BetterReflection\Reflection\Exception\NotAClassReflection
 */
class NotAClassReflectionTest extends PHPUnit_Framework_TestCase
{
    public function testFromInterface()
    {
        $sourceLocator = new SingleFileSourceLocator(__DIR__ . '/../../Fixture/ExampleClass.php');
        $reflector     = new ClassReflector($sourceLocator);

        $exception = NotAClassReflection::fromReflectionClass($reflector->reflect(Fixture\ExampleInterface::class));

        $this->assertInstanceOf(NotAClassReflection::class, $exception);
        $this->assertSame(
            'Provided node "' . Fixture\ExampleInterface::class . '" is not class, but "interface"',
            $exception->getMessage()
        );
    }

    public function testFromTrait()
    {
        $sourceLocator = new SingleFileSourceLocator(__DIR__ . '/../../Fixture/ExampleClass.php');
        $reflector     = new ClassReflector($sourceLocator);

        $exception = NotAClassReflection::fromReflectionClass($reflector->reflect(Fixture\ExampleTrait::class));

        $this->assertInstanceOf(NotAClassReflection::class, $exception);
        $this->assertSame(
            'Provided node "' . Fixture\ExampleTrait::class . '" is not class, but "trait"',
            $exception->getMessage()
        );
    }
}
