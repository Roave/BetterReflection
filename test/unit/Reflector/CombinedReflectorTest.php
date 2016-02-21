<?php

namespace BetterReflectionTest\Reflector;

use BetterReflection\Reflection\ReflectionClass;
use BetterReflection\Reflection\ReflectionFunction;
use BetterReflection\Reflector\CombinedReflector;
use BetterReflection\SourceLocator\Type\SingleFileSourceLocator;

/**
 * @covers \BetterReflection\Reflector\CombinedReflector
 */
class CombinedReflectorTest extends \PHPUnit_Framework_TestCase
{
    public function testGetAll()
    {
        $items = (new CombinedReflector(
            new SingleFileSourceLocator(__DIR__ . '/../Fixture/CombinedReflectorFixture.php')
        ))->getAll();

        $this->assertCount(2, $items);
        $this->assertInstanceOf(ReflectionClass::class, $items[0]);
        $this->assertInstanceOf(ReflectionFunction::class, $items[1]);
    }

    public function testReflectFindsClass()
    {
        $this->assertInstanceOf(ReflectionClass::class, (new CombinedReflector(
            new SingleFileSourceLocator(__DIR__ . '/../Fixture/CombinedReflectorFixture.php')
        ))->reflect('SomeFooClass'));
    }

    public function testReflectFindsFunction()
    {
        $this->assertInstanceOf(ReflectionFunction::class, (new CombinedReflector(
            new SingleFileSourceLocator(__DIR__ . '/../Fixture/CombinedReflectorFixture.php')
        ))->reflect('fooFunc'));
    }

    public function testReflectReturnsNullWhenNothingFound()
    {
        $this->assertNull((new CombinedReflector(
            new SingleFileSourceLocator(__DIR__ . '/../Fixture/CombinedReflectorFixture.php')
        ))->reflect('nothingExistsByThisName'));
    }

    public function testReflectOnLineFindsClass()
    {
        $this->assertInstanceOf(ReflectionClass::class, (new CombinedReflector(
            new SingleFileSourceLocator(__DIR__ . '/../Fixture/CombinedReflectorFixture.php')
        ))->reflectOnLine(6));
    }

    public function testReflectOnLineFindsFunction()
    {
        $this->assertInstanceOf(ReflectionFunction::class, (new CombinedReflector(
            new SingleFileSourceLocator(__DIR__ . '/../Fixture/CombinedReflectorFixture.php')
        ))->reflectOnLine(3));
    }

    public function testReflectOnLineReturnsNullWhenNothingFound()
    {
        $this->assertNull((new CombinedReflector(
            new SingleFileSourceLocator(__DIR__ . '/../Fixture/CombinedReflectorFixture.php')
        ))->reflectOnLine(-1));
    }
}
