<?php

namespace BetterReflectionTest\SourceLocator\Type;

use BetterReflection\Reflector\ClassReflector;
use BetterReflection\SourceLocator\Exception\InvalidDirectory;
use BetterReflection\SourceLocator\Type\DirectorySourceLocator;

/**
 * @covers \BetterReflection\SourceLocator\Type\DirectorySourceLocator
 * @covers \BetterReflection\SourceLocator\Exception\InvalidDirectory
 */
class DirectorySourceLocatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var string
     */
    private $directoryToScan = __DIR__ . '/../../Assets/DirectoryScannerAssets';

    /**
     * @var DirectorySourceLocator
     */
    private $sourceLocator;

    public function setUp()
    {
        $this->sourceLocator = new DirectorySourceLocator([$this->directoryToScan]);
    }

    public function testScanDirectoryClasses()
    {
        $reflector = new ClassReflector($this->sourceLocator);
        $classes = $reflector->getAllClasses();
        $this->assertCount(2, $classes);
        $classNames = [];
        foreach ($classes as $clazz) {
            $classNames[] = $clazz->getName();
        }
        sort($classNames);
        $this->assertEquals('BetterReflectionTest\Assets\DirectoryScannerAssets\Bar\FooBar', $classNames[0]);
        $this->assertEquals('BetterReflectionTest\Assets\DirectoryScannerAssets\Foo', $classNames[1]);
    }

    public function testScanDirectoryFiles()
    {
        $class = new \ReflectionClass(get_class($this->sourceLocator));
        $method = $class->getMethod('scan');
        $method->setAccessible(true);
        $result = $method->invokeArgs($this->sourceLocator, [$this->directoryToScan]);
        $this->assertCount(3, $result);
    }

    public function testInvalidDirectory()
    {
        $this->expectException(InvalidDirectory::class);
        new DirectorySourceLocator([substr($this->directoryToScan, 0, strlen($this->directoryToScan)-1)]);
    }

    public function testInvalidIntegerDirectory()
    {
        $this->expectException(InvalidDirectory::class);
        new DirectorySourceLocator([$this->directoryToScan, 1]);
    }

    public function testInvalidBoleanDirectory()
    {
        $this->expectException(InvalidDirectory::class);
        new DirectorySourceLocator([$this->directoryToScan, true]);
    }

    public function testInvalidObjectDirectory()
    {
        $this->expectException(InvalidDirectory::class);
        new DirectorySourceLocator([$this->directoryToScan, new \stdClass()]);
    }

    public function testInvalidNullDirectory()
    {
        $this->expectException(InvalidDirectory::class);
        new DirectorySourceLocator([$this->directoryToScan, null]);
    }

    public function testExceptionMessage()
    {
        $e = InvalidDirectory::fromNonDirectory('testDir');
        $this->assertEquals(sprintf('%s is not a directory', 'testDir'), $e->getMessage());

        $e = InvalidDirectory::fromNonStringValue(new \stdClass());
        $expected = 'Expected string type of directory, class stdClass given';
        $this->assertEquals($expected, $e->getMessage());

        $e = InvalidDirectory::fromNonStringValue(true);
        $expected = 'Expected string type of directory, boolean given';
        $this->assertEquals($expected, $e->getMessage());

        $e = InvalidDirectory::fromNonStringValue(null);
        $expected = 'Expected string type of directory, null given';
        $this->assertEquals($expected, $e->getMessage());

        $e = InvalidDirectory::fromNonStringValue(100);
        $expected = 'Expected string type of directory, integer given';
        $this->assertEquals($expected, $e->getMessage());

        $e = InvalidDirectory::fromNonStringValue(100.35);
        $expected = 'Expected string type of directory, double given';
        $this->assertEquals($expected, $e->getMessage());
    }
}