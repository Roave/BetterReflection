<?php

namespace BetterReflectionTest\SourceLocator\Type;

use BetterReflection\Reflector\ClassReflector;
use BetterReflection\SourceLocator\Type\FileIteratorSourceLocator;
use BetterReflection\SourceLocator\Type\SingleFileSourceLocator;

/**
 * @covers \BetterReflection\SourceLocator\Type\FileIteratorSourceLocator
 */
class FileIteratorSourceLocatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var string
     */
    private $directoryToScan = __DIR__ . '/../../Assets/DirectoryScannerAssets';

    /**
     * @var FileIteratorSourceLocator
     */
    private $sourceLocator;

    public function setUp()
    {
        $fileSystemIterator = new \RecursiveDirectoryIterator(
            $this->directoryToScan,
            \RecursiveDirectoryIterator::SKIP_DOTS
        );
        $this->sourceLocator = new FileIteratorSourceLocator(new \RecursiveIteratorIterator($fileSystemIterator));
    }

    public function testScanDirectoryClasses()
    {
        $reflector = new ClassReflector($this->sourceLocator);
        $classes = $reflector->getAllClasses();
        $this->assertCount(2, $classes);
        $classNames = [];
        foreach ($classes as $class) {
            $classNames[] = $class->getName();
        }
        sort($classNames);
        $this->assertEquals('BetterReflectionTest\Assets\DirectoryScannerAssets\Bar\FooBar', $classNames[0]);
        $this->assertEquals('BetterReflectionTest\Assets\DirectoryScannerAssets\Foo', $classNames[1]);
    }

    public function testScanDirectoryFiles()
    {
        $fileSystemIteratorSourceLocator = $this->sourceLocator;
        $class = new \ReflectionClass(get_class($fileSystemIteratorSourceLocator));
        $method = $class->getMethod('scan');
        $method->setAccessible(true);
        $result = $method->invoke($fileSystemIteratorSourceLocator);
        $this->assertCount(3, $result);

        // test file path
        $files = [];
        foreach ($result as $file) {
            $class = new \ReflectionClass('BetterReflection\SourceLocator\Type\SingleFileSourceLocator');
            $property = $class->getProperty('filename');
            $property->setAccessible(true);
            $files[] = realpath($property->getValue($file));
        }
        sort($files);
        $this->assertEquals(realpath(__DIR__ . '/../../Assets/DirectoryScannerAssets/Bar/Empty.php'), $files[0]);
        $this->assertEquals(realpath(__DIR__ . '/../../Assets/DirectoryScannerAssets/Bar/FooBar.php'), $files[1]);
        $this->assertEquals(realpath(__DIR__ . '/../../Assets/DirectoryScannerAssets/Foo.php'), $files[2]);

        // test class names
        $classNames = [];
        foreach ($result as $file) {
            /* @var $file SingleFileSourceLocator */
            $reflector = new ClassReflector($file);
            foreach ($reflector->getAllClasses() as $class) {
                $classNames[] = $class->getName();
            }
        }
        sort($classNames);
        $this->assertEquals('BetterReflectionTest\Assets\DirectoryScannerAssets\Bar\FooBar', $classNames[0]);
        $this->assertEquals('BetterReflectionTest\Assets\DirectoryScannerAssets\Foo', $classNames[1]);
        $this->assertCount(2, $classNames);
    }
}
