<?php

namespace BetterReflectionTest\SourceLocator\Type;

use BetterReflection\Reflector\ClassReflector;
use BetterReflection\SourceLocator\Type\DirectorySourceLocator;

/**
 * @covers \BetterReflection\SourceLocator\Type\DirectorySourceLocator
 */
class DirectorySourceLocatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var string[]
     */
    private $directoryToScan = [];

    /**
     * @var DirectorySourceLocator
     */
    private $sourceLocator;

    public function setUp()
    {
        $this->directoryToScan[] = __DIR__ . '/../../Assets/DirectoryScannerAssets';
        $this->directoryToScan[] = __DIR__ . '/../../Assets/DirectoryScannerAssetsFoo';
        $this->sourceLocator = new DirectorySourceLocator($this->directoryToScan);
    }

    public function testScanDirectoryClasses()
    {
        $reflector = new ClassReflector($this->sourceLocator);
        $classes = $reflector->getAllClasses();
        $this->assertCount(4, $classes);
        $classNames = [];
        foreach ($classes as $clazz) {
            $classNames[] = $clazz->getName();
        }
        sort($classNames);
        $this->assertEquals('BetterReflectionTest\Assets\DirectoryScannerAssetsFoo\Bar\FooBar', $classNames[0]);
        $this->assertEquals('BetterReflectionTest\Assets\DirectoryScannerAssetsFoo\Foo', $classNames[1]);
        $this->assertEquals('BetterReflectionTest\Assets\DirectoryScannerAssets\Bar\FooBar', $classNames[2]);
        $this->assertEquals('BetterReflectionTest\Assets\DirectoryScannerAssets\Foo', $classNames[3]);
    }
}
